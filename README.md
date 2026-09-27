# PPTX Template

Biblioteca PHP para manipulação de templates PPTX com substituição de tags por texto e imagens.

## Instalação

```bash
composer require carloscardoso05/pptx-template
```

## Uso Básico

```php
use PptxTemplate\PptxTemplate;

// Carregar template de dados binários
$templateData = file_get_contents('template.pptx');
$template = PptxTemplate::fromData($templateData);

// Substituir tags por texto
$template->setTextValues([
    'nome' => 'João Silva',
    'curso' => 'Desenvolvimento Web',
    'dt_inicio' => '01/01/2026',
    'dt_fim' => '30/06/2026',
    'docente' => 'Maria Santos',
    'ementa' => "Módulo 1 - HTML\nMódulo 2 - CSS\nMódulo 3 - JavaScript",
    'id' => 'CERT-2026-001',
]);

// Adicionar imagem a partir de dados binários
$imageData = file_get_contents('qrcode.png');
$template->addImageFromData('qr_code', $imageData);

// Salvar e retornar dados binários
$outputData = $template->saveToData();
file_put_contents('output.pptx', $outputData);
```

## Integração com Laravel

```php
use PptxTemplate\PptxTemplate;
use Illuminate\Support\Facades\Http;

// Carregar template do Spatie Media Library
$media = $curso->getFirstMedia('modelos');
$template = PptxTemplate::fromData($media->getContent());

// Processar template
$template->setTextValues([
    'nome' => $participante->nome,
    'curso' => $curso->nome,
]);

// Gerar QR Code
$qrcodeData = "CERT-{$participante->id}";
$qrcodeImage = Http::get("https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qrcodeData))->body();
$template->addImageFromData('qr_code', $qrcodeImage);

// Salvar e adicionar ao Spatie
$outputData = $template->saveToData();
$tempPath = storage_path('app/temp/cert_' . uniqid() . '.pptx');
file_put_contents($tempPath, $outputData);

$certificado = $participante->certificado()->create();
$certificado->addMedia($tempPath)->toMediaCollection('certificados');
unlink($tempPath);
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

✅ Trabalha apenas com dados binários em memória  
✅ Preserva todos os estilos originais do template  
✅ Suporta múltiplas linhas em tags de texto  
✅ Substitui tags por imagens mantendo dimensões  
✅ Detecta automaticamente formatos de imagem  
✅ Registra automaticamente novos formatos no Content_Types.xml  
✅ Sem dependências externas  
✅ Perfeito para jobs Laravel  

## Licença

MIT
