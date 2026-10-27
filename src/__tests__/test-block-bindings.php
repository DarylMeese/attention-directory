<?php

class AttentionDirectoryBlockBindingsTest extends WP_UnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        update_option('attention_directory_base_url', 'https://api.example.com');
        update_option('attention_directory_tenant_slug', 'demo');
    }

    public function test_block_bindings_source_returns_value()
    {
        // Mock REST Proxy internal response
        add_filter('pre_http_request', function ($preempt, $parsed_args, $url) {
            if (strpos($url, '/organizations/test-org') !== false) {
                return array(
                    'response' => array('code' => 200),
                    'body' => wp_json_encode(array('name' => 'Bound Org Name', 'ratingAverage' => 4.3))
                );
            }
            return false;
        }, 10, 3);

        // Manually invoke WP Mock Request setup
        $source_args = array('field' => 'name');

        // Create a mock block instance object containing context
        $block_instance = new stdClass();
        $block_instance->context = array('attentionOrganizationSlug' => 'test-org');

        $result = Attention_Directory_Block_Bindings::get_organization_binding_value(
            $source_args,
            $block_instance,
            'content'
        );

        $this->assertEquals('Bound Org Name', $result);

        // Test Rating Number formatting
        $source_args_rating = array('field' => 'rating_average');
        $result_rating = Attention_Directory_Block_Bindings::get_organization_binding_value(
            $source_args_rating,
            $block_instance,
            'content'
        );
        $this->assertEquals('4.3', $result_rating);
    }
}
