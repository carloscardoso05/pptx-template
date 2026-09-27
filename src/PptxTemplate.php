<?php

namespace PptxTemplate;

use ZipArchive;
use PptxTemplate\Exceptions\TemplateNotFoundException;
use PptxTemplate\Exceptions\TagNotFoundException;

/**
 * Classe principal para manipulação de templates PPTX
 */
class PptxTemplate
{
    private string $templatePath;
    private string $outputPath;
    private ZipArchive $zip;
    private TextReplacer $textReplacer;
    private ImageReplacer $imageReplacer;

    public function __construct(string $templatePath)
    {
        if (!file_exists($templatePath)) {
            throw new TemplateNotFoundException("Template não encontrado: {$templatePath}");
        }

        $this->templatePath = $templatePath;
        $this->textReplacer = new TextReplacer();
        $this->imageReplacer = new ImageReplacer();
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
     * Adiciona imagem a partir de um arquivo
     */
    public function addImageFromFile(string $tag, string $filePath): self
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("Arquivo de imagem não encontrado: {$filePath}");
        }

        $imageData = file_get_contents($filePath);
        $this->imageReplacer->addImage($tag, $imageData);
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
     * Salva o arquivo PPTX processado
     */
    public function save(string $outputPath): void
    {
        $this->outputPath = $outputPath;

        // Copiar template
        if (!copy($this->templatePath, $this->outputPath)) {
            throw new \RuntimeException("Erro ao copiar template para: {$this->outputPath}");
        }

        // Abrir como ZipArchive
        $this->zip = new ZipArchive();
        if ($this->zip->open($this->outputPath) !== TRUE) {
            throw new \RuntimeException("Erro ao abrir arquivo PPTX: {$this->outputPath}");
        }

        try {
            $this->processSlides();
            $this->zip->close();
        } catch (\Exception $e) {
            $this->zip->close();
            throw $e;
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
