<?php

class AttentionDirectoryShortcodesTest extends WP_UnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        update_option('attention_directory_base_url', 'https://api.example.com');
        update_option('attention_directory_tenant_slug', 'demo');
    }

    public function test_categories_shortcode_renders()
    {
        // Mock WP_Http response
        add_filter('pre_http_request', function ($preempt, $parsed_args, $url) {
            if (strpos($url, '/directory/navigation') !== false) {
                return array(
                    'response' => array('code' => 200),
                    'body' => wp_json_encode(array('navigation' => array('categories' => array(array('slug' => 'test-cat', 'name' => 'Test Category')))))
                );
            }
            return false;
        }, 10, 3);

        $output = attention_categories_shortcode(array());
        $this->assertStringContainsString('Test Category', $output);
        $this->assertStringContainsString('href=', $output);
    }

    public function test_search_shortcode_renders()
    {
        $output = attention_search_shortcode(array());
        $this->assertStringContainsString('<form', $output);
        $this->assertStringContainsString('type="search"', $output);
        $this->assertStringContainsString('name="search"', $output);
    }
}
