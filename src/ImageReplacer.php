<?php

namespace PptxTemplate;

use ZipArchive;

/**
 * Responsável por substituir tags por imagens
 */
class ImageReplacer
{
    private array $images = [];
    private array $usedFormats = [];

    /**
     * Adiciona uma imagem para substituição
     */
    public function addImage(string $tag, string $imageData): void
    {
        $this->images[$tag] = $imageData;
    }

    /**
     * Retorna todas as imagens registradas
     */
    public function getImages(): array
    {
        return $this->images;
    }

    /**
     * Retorna os formatos de imagem utilizados
     */
    public function getUsedFormats(): array
    {
        return $this->usedFormats;
    }

    /**
     * Substitui uma tag por uma imagem no XML
     */
    public function replace(string $xml, string $tag, string $imageData, ZipArchive $zip, string $slidePath): string
    {
        $placeholder = '{{' . $tag . '}}';
        
        if (strpos($xml, $placeholder) === false) {
            return $xml;
        }

        // Detectar formato da imagem
        $imageDetector = new ImageDetector();
        $format = $imageDetector->detect($imageData);
        $extension = $format['extension'];
        $mimeType = $format['mimeType'];

        // Registrar formato usado
        $this->usedFormats[$extension] = $mimeType;

        // Encontrar o shape que contém a tag
        if (preg_match_all('/<p:sp>.*?<\/p:sp>/s', $xml, $allShapes)) {
            $shapeXml = null;
            foreach ($allShapes[0] as $shape) {
                if (strpos($shape, $placeholder) !== false) {
                    $shapeXml = $shape;
                    break;
                }
            }

            if ($shapeXml) {
                // Extrair dimensões do shape
                preg_match('/<a:off x="(\d+)" y="(\d+)"/', $shapeXml, $offsetMatch);
                preg_match('/<a:ext cx="(\d+)" cy="(\d+)"/', $shapeXml, $extentMatch);

                $x = $offsetMatch[1] ?? '0';
                $y = $offsetMatch[2] ?? '0';
                $cx = $extentMatch[1] ?? '0';
                $cy = $extentMatch[2] ?? '0';

                // Gerar nome único para a imagem
                $imageName = 'image_' . $tag . '_' . uniqid() . '.' . $extension;
                $imagePath = 'ppt/media/' . $imageName;

                // Adicionar imagem ao ZIP
                $zip->addFromString($imagePath, $imageData);

                // Atualizar relações do slide
                $relsPath = dirname($slidePath) . '/_rels/' . basename($slidePath) . '.rels';
                $slideRels = $zip->getFromName($relsPath);

                // Encontrar próximo rId disponível
                preg_match_all('/Id="rId(\d+)"/', $slideRels, $rIdMatches);
                $maxRId = 0;
                foreach ($rIdMatches[1] as $rId) {
                    $maxRId = max($maxRId, intval($rId));
                }
                $newRId = 'rId' . ($maxRId + 1);

                // Adicionar nova relação
                $newRel = '<Relationship Id="' . $newRId . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/' . $imageName . '"/>';
                $slideRels = str_replace('</Relationships>', $newRel . '</Relationships>', $slideRels);
                $zip->addFromString($relsPath, $slideRels);

                // Criar XML da imagem
                $picXml = '<p:pic>' .
                    '<p:nvPicPr>' .
                    '<p:cNvPr id="' . (100 + count($this->images)) . '" name="Image ' . $tag . '"/>' .
                    '<p:cNvPicPr>' .
                    '<a:picLocks noChangeArrowheads="1" noChangeAspect="1"/>' .
                    '</p:cNvPicPr>' .
                    '<p:nvPr/>' .
                    '</p:nvPicPr>' .
                    '<p:blipFill>' .
                    '<a:blip r:embed="' . $newRId . '"/>' .
                    '<a:stretch><a:fillRect/></a:stretch>' .
                    '</p:blipFill>' .
                    '<p:spPr>' .
                    '<a:xfrm>' .
                    '<a:off x="' . $x . '" y="' . $y . '"/>' .
                    '<a:ext cx="' . $cx . '" cy="' . $cy . '"/>' .
                    '</a:xfrm>' .
                    '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom>' .
                    '</p:spPr>' .
                    '</p:pic>';

                // Substituir shape por imagem
                $xml = str_replace($shapeXml, $picXml, $xml);
            }
        }

        return $xml;
    }
}
