<?php

declare(strict_types=1);

namespace NoCodeDbtTransformation\FileDumper\OutputManifest;

interface OutputManifestInterface
{
    public function dump(): void;
}
