<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class StoreMessageRequest extends FormRequest
{
    /**
     * Office (.doc/.docx/.xls/.xlsx) es notoriamente inconsistente con la
     * detección de tipo por contenido de Laravel (`mimes:`): dependiendo de
     * qué programa generó el archivo, a veces se detecta como zip u OLE
     * genérico y la regla lo rechaza aunque sea un documento válido. Se
     * valida contra la extensión del nombre original en su lugar — seguro
     * en este contexto porque solo miembros autenticados de un canal privado
     * pueden subir/descargar, y el archivo nunca se ejecuta en el servidor.
     */
    public const ALLOWED_EXTENSIONS = ['pdf', 'xls', 'xlsx', 'doc', 'docx', 'csv', 'jpg', 'jpeg', 'png', 'gif', 'webp'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['required_without:file', 'nullable', 'string', 'max:4000'],
            'theme_id' => ['nullable', 'integer'],
            'thread_id' => ['nullable', 'integer'],
            'file' => [
                'nullable',
                'file',
                'max:25600',
                function ($attribute, $value, $fail) {
                    /** @var UploadedFile $value */
                    $extension = strtolower($value->getClientOriginalExtension());

                    if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                        $fail('El archivo debe ser de tipo: '.implode(', ', self::ALLOWED_EXTENSIONS).'.');
                    }
                },
            ],
        ];
    }
}
