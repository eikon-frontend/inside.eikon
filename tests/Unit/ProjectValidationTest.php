<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ProjectValidationTest extends TestCase
{
    /**
     * Test de la logique de validation (simulée)
     */
    public function test_project_without_title_is_invalid()
    {
        $data = [
            'post_type' => 'project',
            'post_title' => '',
            'post_status' => 'publish'
        ];

        // Simulation de la logique de validation de validation.php
        $errors = [];
        if (empty($data['post_title'])) {
            $errors[] = 'Le titre du projet est obligatoire.';
        }

        if (!empty($errors)) {
            $data['post_status'] = 'draft';
        }

        $this->assertEquals('draft', $data['post_status']);
        $this->assertContains('Le titre du projet est obligatoire.', $errors);
    }
}
