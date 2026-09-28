<?php

namespace PptxTemplate;

use ZipArchive;

/**
 * Classe principal para manipulação de templates PPTX
 */
class PptxTemplate
{
    private string $templateData;
    private ZipArchive $zip;
    private TextReplacer $textReplacer;
    private ImageReplacer $imageReplacer;

    private function __construct(string $templateData)
    {
        $this->templateData = $templateData;
        $this->textReplacer = new TextReplacer();
        $this->imageReplacer = new ImageReplacer();
    }

    /**
     * Cria uma instância a partir de dados binários do template
     */
    public static function fromData(string $templateData): self
    {
        return new self($templateData);
    }

    /**
     * Define valores para substituição de tags por texto
     */
    public function setTextValues(array $values): self
    {
        $this->textReplacer->setValues($values);
        return $this;
    }

    /**
     * Adiciona imagem a partir de dados binários
     */
    public function addImageFromData(string $tag, string $imageData): self
    {
        $this->imageReplacer->addImage($tag, $imageData);
        return $this;
    }

    /**
     * Salva o arquivo PPTX processado e retorna os dados binários
     */
    public function saveToData(): string
    {
        // Criar arquivo temporário para ZipArchive
        $tempFile = tempnam(sys_get_temp_dir(), 'pptx_');
        $zipOpened = false;
        
        try {
            // Escrever template no arquivo temporário
            file_put_contents($tempFile, $this->templateData);

            // Abrir como ZipArchive
            $this->zip = new ZipArchive();
            if ($this->zip->open($tempFile) !== TRUE) {
                throw new \RuntimeException("Erro ao abrir arquivo PPTX");
            }
            $zipOpened = true;

            $this->processSlides();
            
            // Fechar o zip antes de ler os dados
            $this->zip->close();
            $zipOpened = false;

            // Ler dados processados
            $outputData = file_get_contents($tempFile);
            
            return $outputData;
        } finally {
            // Garantir que o ZipArchive seja fechado antes do unlink
            if ($zipOpened && isset($this->zip)) {
                $this->zip->close();
            }
            
            // Limpar arquivo temporário
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * Processa todos os slides do template
     */
    private function processSlides(): void
    {
        $xmlManipulator = new XmlManipulator($this->zip);
        $slides = $xmlManipulator->getSlides();

        foreach ($slides as $slidePath) {
            $xml = $this->zip->getFromName($slidePath);
            
            // Substituir tags de texto
            $xml = $this->textReplacer->replace($xml);

            // Substituir tags de imagem
            $imageTags = $this->imageReplacer->getImages();
            foreach ($imageTags as $tag => $imageData) {
                if (strpos($xml, '{{' . $tag . '}}') !== false) {
                    $xml = $this->imageReplacer->replace(
                        $xml,
                        $tag,
                        $imageData,
                        $this->zip,
                        $slidePath
                    );
                }
            }

            $this->zip->addFromString($slidePath, $xml);
        }

        // Atualizar Content_Types.xml
        $this->updateContentTypes();
    }

    /**
     * Atualiza o arquivo Content_Types.xml com os formatos de imagem adicionados
     */
    private function updateContentTypes(): void
    {
        $contentTypes = $this->zip->getFromName('[Content_Types].xml');
        $imageFormats = $this->imageReplacer->getUsedFormats();

        foreach ($imageFormats as $extension => $mimeType) {
            $extensionTag = 'Extension="' . $extension . '"';
            if (strpos($contentTypes, $extensionTag) === false) {
                $newType = '<Default ContentType="' . $mimeType . '" Extension="' . $extension . '"/>';
                $contentTypes = str_replace('</Types>', $newType . '</Types>', $contentTypes);
            }
        }

        $this->zip->addFromString('[Content_Types].xml', $contentTypes);
    }
}
