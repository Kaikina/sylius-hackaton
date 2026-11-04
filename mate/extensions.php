<?php

// This file is managed by Mate - use `discover` or `skills:*` commands
// over manual editing. Only changes to `mode` or `enabled` are kept,
// every other key is overwritten by Mate.

return [
    'sylius/sylius-mate-extension' => [
        'enabled' => true,
        'skills' => [
            'sylius-dev' => [
                'enabled' => true,
                'mode' => 'managed',
                'state' => 'managed',
                'source' => 'vendor/sylius/sylius-mate-extension/skills/sylius-dev',
                'source_hash' => 'sha256:afa9cc09ed44d7c03025f6775b55c745034a951e4ccaffaa1ebdb16aa07ac257',
                'hash' => 'sha256:48640cf5b37d318ec80eb9860f2b2ef16815a15cd2fc9f0b148beffd35019545',
                'targets' => [
                    '.agents/skills/mate-sylius-dev',
                    '.claude/skills/mate-sylius-dev',
                ],
            ],
            'sylius-events' => [
                'enabled' => true,
                'mode' => 'managed',
                'state' => 'managed',
                'source' => 'vendor/sylius/sylius-mate-extension/skills/sylius-events',
                'source_hash' => 'sha256:25856965b43bddae4b979e5fac07b1d709ebe781ff52bfd0ef05ae0389f30679',
                'hash' => 'sha256:1c44aa02a92ded3747470b3fe4da228a0b58441fc21a9269575bb05c96aba5a2',
                'targets' => [
                    '.agents/skills/mate-sylius-events',
                    '.claude/skills/mate-sylius-events',
                ],
            ],
            'sylius-frontend' => [
                'enabled' => true,
                'mode' => 'managed',
                'state' => 'managed',
                'source' => 'vendor/sylius/sylius-mate-extension/skills/sylius-frontend',
                'source_hash' => 'sha256:f3dab5c89469210432a765cfc29cec1342168fe988a5608ed3394022595bd46e',
                'hash' => 'sha256:808c8ef1e61845ee50af8112c1c0512232d344c00de91416fbae3d7ed9b7ac1a',
                'targets' => [
                    '.agents/skills/mate-sylius-frontend',
                    '.claude/skills/mate-sylius-frontend',
                ],
            ],
            'sylius-mailer' => [
                'enabled' => true,
                'mode' => 'managed',
                'state' => 'managed',
                'source' => 'vendor/sylius/sylius-mate-extension/skills/sylius-mailer',
                'source_hash' => 'sha256:df7f96dc4e3ce986e3225127462dabe25379ae3252869e17ae63781f509d4bc6',
                'hash' => 'sha256:033d26d726cebff527b62a403e38721e4d1ceacb982138944ab2efb37cca60b4',
                'targets' => [
                    '.agents/skills/mate-sylius-mailer',
                    '.claude/skills/mate-sylius-mailer',
                ],
            ],
            'sylius-resource' => [
                'enabled' => true,
                'mode' => 'managed',
                'state' => 'managed',
                'source' => 'vendor/sylius/sylius-mate-extension/skills/sylius-resource',
                'source_hash' => 'sha256:14b28ab9ad8a180d0573e1904c585349e1c418cf369c552e988cd0c01c503d7d',
                'hash' => 'sha256:fd99bb2171c47ca577007c70f9465c2b90a03bc73737b5594020168020a093da',
                'targets' => [
                    '.agents/skills/mate-sylius-resource',
                    '.claude/skills/mate-sylius-resource',
                ],
            ],
            'sylius-verify' => [
                'enabled' => true,
                'mode' => 'managed',
                'state' => 'managed',
                'source' => 'vendor/sylius/sylius-mate-extension/skills/sylius-verify',
                'source_hash' => 'sha256:0f0e818dded5d69597404658d1750c0cba756b9bac7399591c9f9f63344ecf6e',
                'hash' => 'sha256:f82f939493c5a198b48fc70911aafeaecdea7a2ad73f536417d9a383996d2f57',
                'targets' => [
                    '.agents/skills/mate-sylius-verify',
                    '.claude/skills/mate-sylius-verify',
                ],
            ],
        ],
    ],
    'symfony/ai-mate' => [
        'enabled' => true,
        'skills' => [
            'php-environment-check' => [
                'enabled' => true,
                'mode' => 'managed',
                'state' => 'managed',
                'source' => 'vendor/symfony/ai-mate/skills/php-environment-check',
                'source_hash' => 'sha256:475400c87571d228335fb80f414c56a80110e182268df65f9f84bc9bfb2aa6f3',
                'hash' => 'sha256:56de92962de6233284c439de64b1cef66487c56c07e6c3357658139cf4d82527',
                'targets' => [
                    '.agents/skills/mate-php-environment-check',
                    '.claude/skills/mate-php-environment-check',
                ],
            ],
            'system-information' => [
                'enabled' => true,
                'mode' => 'managed',
                'state' => 'managed',
                'source' => 'vendor/symfony/ai-mate/skills/system-information',
                'source_hash' => 'sha256:7249544b603ecd416fae22cd92b465257619306f88a50dc8efd9653606e9e460',
                'hash' => 'sha256:fcbfe6ea831b35299f5ba646bf63dbd761e184e7ad196087a350a060f9915973',
                'targets' => [
                    '.agents/skills/mate-system-information',
                    '.claude/skills/mate-system-information',
                ],
            ],
        ],
    ],
    'symfony/ai-symfony-mate-extension' => [
        'enabled' => true,
        'skills' => [
            'symfony-dotenv-diagnostics' => [
                'enabled' => true,
                'mode' => 'managed',
                'state' => 'managed',
                'source' => 'vendor/symfony/ai-symfony-mate-extension/skills/symfony-dotenv-diagnostics',
                'source_hash' => 'sha256:527b1145e7386d15260509f88da26cb3158e17ae140f14c672ed299da1878ab0',
                'hash' => 'sha256:f4a3a9983496173dcdb8e27ed402a73e0f03d763060cbb668db1b993016d1f62',
                'targets' => [
                    '.agents/skills/mate-symfony-dotenv-diagnostics',
                    '.claude/skills/mate-symfony-dotenv-diagnostics',
                ],
            ],
            'symfony-profiler-debugging' => [
                'enabled' => true,
                'mode' => 'managed',
                'state' => 'managed',
                'source' => 'vendor/symfony/ai-symfony-mate-extension/skills/symfony-profiler-debugging',
                'source_hash' => 'sha256:bf384469e0e92af2b40ede7367090e4a41ff48f75a11f2ac5e64a2e4335068be',
                'hash' => 'sha256:adddef46d3cf8613d2d664f5f214e91572b6ae859406bd4d1d0196c11af1f7cb',
                'targets' => [
                    '.agents/skills/mate-symfony-profiler-debugging',
                    '.claude/skills/mate-symfony-profiler-debugging',
                ],
            ],
            'symfony-request-triage' => [
                'enabled' => true,
                'mode' => 'managed',
                'state' => 'managed',
                'source' => 'vendor/symfony/ai-symfony-mate-extension/skills/symfony-request-triage',
                'source_hash' => 'sha256:3b10b98407be5b6081423aeb8146b16eb81984264e50f2a5a38ac1706cc65581',
                'hash' => 'sha256:5d2c994451acdd9c07b18eba18833a15d7598b7f2cab3b7f8c5346ebc3928f4e',
                'targets' => [
                    '.agents/skills/mate-symfony-request-triage',
                    '.claude/skills/mate-symfony-request-triage',
                ],
            ],
            'symfony-service-inspection' => [
                'enabled' => true,
                'mode' => 'managed',
                'state' => 'managed',
                'source' => 'vendor/symfony/ai-symfony-mate-extension/skills/symfony-service-inspection',
                'source_hash' => 'sha256:6fa685f2b72129b96754ca1aff0e533f1c04fb5ff3a9ceddafab9059142bc0c8',
                'hash' => 'sha256:d4f0247d8c0355d714a1b6c8a738b2200bc802cf3c1f402a73b6866f164f14c1',
                'targets' => [
                    '.agents/skills/mate-symfony-service-inspection',
                    '.claude/skills/mate-symfony-service-inspection',
                ],
            ],
        ],
    ],
];
