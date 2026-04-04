<?php 

declare(strict_types=1); 

namespace Tests\Feature\Api; 

use Tests\TestCase; 

final class HealthEndpointTest extends TestCase { 

    public function test_health_endpoint_returns_success_payload(): void { 
        $this->getJson('/api/v1/health')->assertOk()->assertJson(['success' => true]); 
    } 
    
    public function test_health_endpoint_returns_request_and_trace_headers(): void { 
        $response = $this->getJson('/api/v1/health'); 
        $response->assertHeader('X-Request-Id'); 
        $response->assertHeader('X-Trace-Id'); 
    } 
}
