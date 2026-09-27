<?php

namespace PptxTemplate;

use ZipArchive;

/**
 * Manipula arquivos XML dentro do PPTX
 */
class XmlManipulator
{
    private ZipArchive $zip;

    public function __construct(ZipArchive $zip)
    {
        $this->zip = $zip;
    }

    /**
     * Retorna todos os slides do PPTX
     */
    public function getSlides(): array
    {
        $slides = [];
        $numFiles = $this->zip->numFiles;

        for ($i = 0; $i < $numFiles; $i++) {
            $filename = $this->zip->getNameIndex($i);
            if (preg_match('/^ppt\/slides\/slide\d+\.xml$/', $filename)) {
                $slides[] = $filename;
            }
        }

        return $slides;
    }

    /**
     * Busca um shape que contém uma tag específica
     */
    public function findShapeByTag(string $xml, string $tag): ?string
    {
        $placeholder = '{{' . $tag . '}}';
        
        if (preg_match_all('/<p:sp>.*?<\/p:sp>/s', $xml, $allShapes)) {
            foreach ($allShapes[0] as $shape) {
                if (strpos($shape, $placeholder) !== false) {
                    return $shape;
                }
            }
        }

        return null;
    }

    /**
     * Extrai dimensões de um shape
     */
    public function extractDimensions(string $shapeXml): array
    {
        preg_match('/<a:off x="(\d+)" y="(\d+)"/', $shapeXml, $offsetMatch);
        preg_match('/<a:ext cx="(\d+)" cy="(\d+)"/', $shapeXml, $extentMatch);

        return [
            'x' => $offsetMatch[1] ?? '0',
            'y' => $offsetMatch[2] ?? '0',
            'cx' => $extentMatch[1] ?? '0',
            'cy' => $extentMatch[2] ?? '0',
        ];
    }

    /**
     * Atualiza as relações de um slide
     */
    public function addRelationship(string $slidePath, string $target, string $type): string
    {
        $relsPath = dirname($slidePath) . '/_rels/' . basename($slidePath) . '.rels';
        $slideRels = $this->zip->getFromName($relsPath);

        // Encontrar próximo rId disponível
        preg_match_all('/Id="rId(\d+)"/', $slideRels, $rIdMatches);
        $maxRId = 0;
        foreach ($rIdMatches[1] as $rId) {
            $maxRId = max($maxRId, intval($rId));
        }
        $newRId = 'rId' . ($maxRId + 1);

        // Adicionar nova relação
        $newRel = '<Relationship Id="' . $newRId . '" Type="' . $type . '" Target="' . $target . '"/>';
        $slideRels = str_replace('</Relationships>', $newRel . '</Relationships>', $slideRels);

        $this->zip->addFromString($relsPath, $slideRels);

        return $newRId;
    }
}
