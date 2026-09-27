<?php

namespace PptxTemplate;

/**
 * Responsável por substituir tags por texto preservando estilos
 */
class TextReplacer
{
    private array $values = [];

    /**
     * Define os valores das tags
     */
    public function setValues(array $values): void
    {
        $this->values = $values;
    }

    /**
     * Substitui todas as tags de texto no XML
     */
    public function replace(string $xml): string
    {
        foreach ($this->values as $tag => $value) {
            $placeholder = '{{' . $tag . '}}';
            
            if (strpos($xml, $placeholder) !== false) {
                // Verificar se é uma tag de múltiplas linhas (como conteúdo)
                if (strpos($value, "\n") !== false) {
                    $xml = $this->replaceMultiLineTag($xml, $tag, $value);
                } else {
                    $escapedValue = htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                    $xml = str_replace($placeholder, $escapedValue, $xml);
                }
            }
        }

        return $xml;
    }

    /**
     * Substitui tags que contêm múltiplas linhas, criando parágrafos separados
     */
    private function replaceMultiLineTag(string $xml, string $tag, string $value): string
    {
        $placeholder = '{{' . $tag . '}}';
        
        // Encontrar o run que contém a tag
        if (preg_match('/<a:r>.*?' . preg_quote($placeholder, '/') . '.*?<\/a:r>/s', $xml, $matches)) {
            $runXml = $matches[0];
            
            // Extrair o rPr (propriedades de formatação)
            preg_match('/<a:rPr.*?<\/a:rPr>/s', $runXml, $rPrMatch);
            $rPrXml = $rPrMatch[0] ?? '';
            
            // Extrair o pPr do parágrafo que contém a tag
            preg_match('/<a:p>(?:(?!<a:p>).)*?' . preg_quote($placeholder, '/') . '.*?<\/a:p>/s', $xml, $pMatch);
            preg_match('/<a:pPr.*?<\/a:pPr>/s', $pMatch[0] ?? '', $pPrMatch);
            $pPrXml = $pPrMatch[0] ?? '';
            
            // Dividir conteúdo por quebras de linha
            $lines = explode("\n", $value);
            $paragraphs = '';
            
            foreach ($lines as $line) {
                $escapedLine = htmlspecialchars($line, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $paragraphs .= '<a:p>' . $pPrXml .
                    '<a:r>' . $rPrXml . '<a:t>' . $escapedLine . '</a:t></a:r>' .
                    '</a:p>';
            }
            
            // Substituir apenas o parágrafo que contém a tag
            $xml = preg_replace(
                '/<a:p>(?:(?!<a:p>).)*?' . preg_quote($placeholder, '/') . '.*?<\/a:p>/s',
                $paragraphs,
                $xml
            );
        }

        return $xml;
    }
}
