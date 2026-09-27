<?php

namespace PptxTemplate;

use PptxTemplate\Exceptions\InvalidImageException;

/**
 * Detecta o formato de imagens a partir de magic bytes
 */
class ImageDetector
{
    /**
     * Magic bytes de formatos suportados
     */
    private const MAGIC_BYTES = [
        'png' => [
            'bytes' => "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A",
            'mime' => 'image/png',
        ],
        'jpg' => [
            'bytes' => "\xFF\xD8\xFF",
            'mime' => 'image/jpeg',
        ],
        'gif' => [
            'bytes' => "\x47\x49\x46\x38",
            'mime' => 'image/gif',
        ],
        'bmp' => [
            'bytes' => "\x42\x4D",
            'mime' => 'image/bmp',
        ],
    ];

    /**
     * Detecta o formato da imagem a partir dos magic bytes
     * 
     * @return array{extension: string, mimeType: string}
     * @throws InvalidImageException
     */
    public function detect(string $imageData): array
    {
        if (empty($imageData)) {
            throw new InvalidImageException("Dados de imagem vazios");
        }

        foreach (self::MAGIC_BYTES as $extension => $info) {
            $magicBytes = $info['bytes'];
            $length = strlen($magicBytes);

            if (substr($imageData, 0, $length) === $magicBytes) {
                return [
                    'extension' => $extension,
                    'mimeType' => $info['mime'],
                ];
            }
        }

        throw new InvalidImageException("Formato de imagem não suportado. Formatos aceitos: PNG, JPG, GIF, BMP");
    }

    /**
     * Valida se os dados são uma imagem válida
     */
    public function isValid(string $imageData): bool
    {
        try {
            $this->detect($imageData);
            return true;
        } catch (InvalidImageException $e) {
            return false;
        }
    }
}
