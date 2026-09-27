# PPTX Template

Biblioteca PHP para manipulação de templates PPTX com substituição de tags por texto e imagens.

## Instalação

```bash
composer require pptx-template/pptx-template
```

## Uso Básico

```php
use PptxTemplate\PptxTemplate;

$template = new PptxTemplate('template.pptx');

// Substituir tags por texto
$template->setTextValues([
    'nome' => 'João Silva',
    'curso' => 'Desenvolvimento Web',
    'dt_inicio' => '01/01/2026',
    'dt_fim' => '30/06/2026',
    'docente' => 'Maria Santos',
    'conteudo' => "Módulo 1 - HTML\nMódulo 2 - CSS\nMódulo 3 - JavaScript",
    'id' => 'CERT-2026-001',
]);

// Adicionar imagem a partir de arquivo
$template->addImageFromFile('qr_code', '/caminho/para/qrcode.png');

// Ou adicionar imagem a partir de dados binários
$imageData = file_get_contents('https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=CERT-2026-001');
$template->addImageFromData('qr_code', $imageData);

// Salvar arquivo processado
$template->save('output.pptx');
```

## Tags no Template

As tags no template PPTX devem estar no formato `{{tag}}`:

- **Tags de texto**: `{{nome}}`, `{{curso}}`, etc.
- **Tags de imagem**: `{{qr_code}}`, `{{logo}}`, etc.

### Exemplo de Template

No PowerPoint, crie text boxes com as tags:

```
Certificamos que {{nome}} concluiu o curso {{curso}} 
ministrado pela professora {{docente}} entre os dias 
{{dt_inicio}} e {{dt_fim}}.

ID: {{id}}

{{qr_code}}
```

## Formatos de Imagem Suportados

- PNG (image/png)
- JPG/JPEG (image/jpeg)
- GIF (image/gif)
- BMP (image/bmp)

A biblioteca detecta automaticamente o formato da imagem a partir dos magic bytes.

## Características

✅ Preserva todos os estilos originais do template  
✅ Suporta múltiplas linhas em tags de texto  
✅ Substitui tags por imagens mantendo dimensões  
✅ Detecta automaticamente formatos de imagem  
✅ Registra automaticamente novos formatos no Content_Types.xml  

## Exemplo Completo com QR Code

```php
use PptxTemplate\PptxTemplate;

// Dados do certificado
$dados = [
    'nome' => 'João Silva',
    'curso' => 'Desenvolvimento Web',
    'dt_inicio' => '01/01/2026',
    'dt_fim' => '30/06/2026',
    'docente' => 'Maria Santos',
    'conteudo' => "Módulo 1 - HTML\nMódulo 2 - CSS\nMódulo 3 - JavaScript",
    'id' => 'CERT-2026-001',
];

// Gerar QR Code
$qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($dados['id']);
$qrCodeImage = file_get_contents($qrCodeUrl);

// Processar template
$template = new PptxTemplate('template.pptx');
$template->setTextValues($dados);
$template->addImageFromData('qr_code', $qrCodeImage);
$template->save('certificado.pptx');
```

## Licença

MIT
