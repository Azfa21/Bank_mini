<?php

namespace App\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCodeService
{
    public function svg(string $value, int $size = 220, int $margin = 4): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size, $margin),
            new SvgImageBackEnd
        );

        $writer = new Writer($renderer);

        return $this->withWhiteBackground(@$writer->writeString($value));
    }

    protected function withWhiteBackground(string $svg): string
    {
        return (string) preg_replace(
            '/<svg([^>]*)>/',
            '$0<rect width="100%" height="100%" fill="#ffffff"/>',
            $svg,
            1,
        );
    }
}
