<?php

namespace Tygh\Tests\Unit\Security;

use Tygh\Tests\Unit\ATestCase;

class SecurityHttpContentPolicy extends ATestCase
{
    private $directives = [
        'base-uri' => [
            'self' => true
        ],
        'default-src' => [
            'self' => true
        ],
        'child-src' => [
            'allow' => [
                'https://site1',
                'https://site2'
            ],
            'self' => false
        ],
        'connect-src' => [
            'self' => true
        ],
        'form-action' => [
            'allow' => [
                'https://site5.com',
                'https://site6.com'
            ],
            'self' => true
        ],
        'frame-ancestors' => [
            'allow' => [
                'site7.com',
                'http://site8.com'
            ]
        ],
        'img-src' => [
            'self' => true,
            'data' => true
        ],
        'media-src' => [],
        'object-src' => [],
        'plugin-types' => [],
        'script-src' => [
            'allow' => [
                'https://site9.com',
                'https://site10.com',
                'https://site11.com',
            ],
            'hashes' => [
                'sha256' => 'lJkxpnH64OVexOG7shj9ToRfQMV0MaYF+m+BzZH8Xqg='
            ],
            'self' => true,
            'unsafe-inline' => false,
            'unsafe-eval' => false
        ],
        'style_src' => [
            'self' => true
        ]
    ];

    private $result_header = "Content-Security-Policy: base-uri 'self'; default-src 'self'; child-src https://site1 https://site2; connect-src 'self'; form-action 'self' https://site5.com https://site6.com; frame-ancestors https://site7.com http://site7.com http://site8.com; img-src 'self' data:; script-src 'self' https://site9.com https://site10.com https://site11.com 'sha256-lJkxpnH64OVexOG7shj9ToRfQMV0MaYF+m+BzZH8Xqg='; style_src 'self'; ";

    protected function setUp(): void
    {
        $this->requireCore('functions/fn.common.php');

        parent::setUp();
    }

    public function testBase()
    {
        $header = fn_build_content_security_policy_header($this->directives);

        $this->assertEquals(
            $header,
            $this->result_header
        );
    }

    public function testNone()
    {
        $directives = [
            'object-src' => [
                'none' => true
            ]
        ];

        $header = fn_build_content_security_policy_header($directives);

        $this->assertEquals(
            $this->combine("object-src 'none'; "),
            $header
        );
    }

    public function testAllowDataUris()
    {
        $directives = [
            'img-src' => [
                'data' => true
            ]
        ];

        $header = fn_build_content_security_policy_header($directives);

        $this->assertContains(
            "data:",
            $header
        );
    }

    public function testSandbox()
    {
        $directives = [
            'sandbox' => [
                'allow' => [
                    'allow-scripts'
                ]
            ]
        ];

        $header = fn_build_content_security_policy_header($directives);

        $this->assertEquals(
            $this->combine('sandbox allow-scripts; '),
            $header
        );

        $directives = [
            'sandbox' => true
        ];

        $header = fn_build_content_security_policy_header($directives);

        $this->assertEquals(
            $this->combine('sandbox; '),
            $header
        );
    }

    public function testAllowUnsafeEval()
    {
        $directives = [
            'script-src' => [
                'unsafe-eval' => true
            ]
        ];

        $header = fn_build_content_security_policy_header($directives);

        $this->assertContains("'unsafe-eval'", $header);
    }

    public function testAllowUnsafeInline()
    {
        $directives = [
            'script-src' => [
                'unsafe-inline' => true
            ]
        ];

        $header = fn_build_content_security_policy_header($directives);

        $this->assertContains("'unsafe-inline'", $header);
    }

    public function testHash()
    {
        $script = "<sсript>console.log('Hello, World!');</sсript>";

        $hash = base64_encode(hash('sha384', $script, true));

        $directives = [
            'script-src' => [
                'hashes' => [
                    'sha384' => $hash
                ]
            ]
        ];

        $header = fn_build_content_security_policy_header($directives);

        $this->assertEquals(
            $this->combine("script-src 'sha384-$hash'; "),
            $header
        );
    }

    protected function combine($header)
    {
        return 'Content-Security-Policy: ' . $header;
    }
}
