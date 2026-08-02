<?php

/**
 * ACF/SCF field group registration: page builder ("sections" flexible content).
 *
 * Registered in PHP (not built through the admin UI) so the field group is
 * versioned in git like any other code, rather than relying on ACF's Local
 * JSON auto-export to a separate `acf-json/` directory that then has to be
 * kept in sync with the UI. See .claude/prompts/WORDPRESS-PROCESS.md §7 for the
 * rationale.
 */

namespace App\Theme;

add_action('acf/init', function () {
    $url_field_instructions = 'Chemin relatif (/contact) ou URL absolue.';

    acf_add_local_field_group([
        'key' => 'group_page_sections',
        'title' => 'Sections de page',
        'fields' => [
            [
                'key' => 'field_sections',
                'label' => 'Sections',
                'name' => 'sections',
                'type' => 'flexible_content',
                'button_label' => 'Ajouter une section',
                'layouts' => [
                    'layout_hero' => [
                        'key' => 'layout_hero',
                        'name' => 'hero',
                        'label' => 'Hero',
                        'display' => 'block',
                        'min' => '',
                        'max' => 1,
                        'sub_fields' => [
                            [
                                'key' => 'field_hero_title',
                                'label' => 'Titre',
                                'name' => 'title',
                                'type' => 'text',
                                'required' => 1,
                            ],
                            [
                                'key' => 'field_hero_subtitle',
                                'label' => 'Sous-titre',
                                'name' => 'subtitle',
                                'type' => 'textarea',
                                'rows' => 2,
                            ],
                            [
                                'key' => 'field_hero_media',
                                'label' => 'Média',
                                'name' => 'media',
                                'type' => 'image',
                                'return_format' => 'array',
                            ],
                            [
                                'key' => 'field_hero_cta_label',
                                'label' => 'Libellé du bouton',
                                'name' => 'cta_label',
                                'type' => 'text',
                            ],
                            [
                                'key' => 'field_hero_cta_url',
                                'label' => 'URL du bouton',
                                'name' => 'cta_url',
                                'type' => 'text',
                                'instructions' => $url_field_instructions,
                                'conditional_logic' => [
                                    [
                                        [
                                            'field' => 'field_hero_cta_label',
                                            'operator' => '!=empty',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'layout_section' => [
                        'key' => 'layout_section',
                        'name' => 'section',
                        'label' => 'Section (colonnes)',
                        'display' => 'block',
                        'sub_fields' => [
                            [
                                'key' => 'field_section_columns',
                                'label' => 'Colonnes',
                                'name' => 'columns',
                                'type' => 'select',
                                'choices' => ['1' => '1', '2' => '2', '3' => '3'],
                                'default_value' => '1',
                                'required' => 1,
                                'instructions' => 'Nombre de colonnes de la grille (classes Tailwind, jamais de concaténation dynamique).',
                            ],
                            [
                                'key' => 'field_section_content',
                                'label' => 'Contenu',
                                'name' => 'content',
                                'type' => 'flexible_content',
                                'button_label' => 'Ajouter un bloc',
                                'instructions' => 'Les autres layouts ne sont insérables que dans une section (règle éditoriale de .claude/prompts/WORDPRESS.md §7d).',
                                'layouts' => [
                                    'layout_text_media' => [
                                        'key' => 'layout_text_media',
                                        'name' => 'text_media',
                                        'label' => 'Texte + média',
                                        'display' => 'block',
                                        'sub_fields' => [
                                            [
                                                'key' => 'field_text_media_body',
                                                'label' => 'Texte',
                                                'name' => 'body',
                                                'type' => 'wysiwyg',
                                                'tabs' => 'visual',
                                                'media_upload' => 0,
                                            ],
                                            [
                                                'key' => 'field_text_media_media',
                                                'label' => 'Média',
                                                'name' => 'media',
                                                'type' => 'image',
                                                'return_format' => 'array',
                                            ],
                                            [
                                                'key' => 'field_text_media_position',
                                                'label' => 'Position du média',
                                                'name' => 'position',
                                                'type' => 'select',
                                                'choices' => ['left' => 'Gauche', 'right' => 'Droite'],
                                                'default_value' => 'right',
                                            ],
                                        ],
                                    ],
                                    'layout_cards_grid' => [
                                        'key' => 'layout_cards_grid',
                                        'name' => 'cards_grid',
                                        'label' => 'Grille de cartes',
                                        'display' => 'block',
                                        'sub_fields' => [
                                            [
                                                'key' => 'field_cards_grid_columns',
                                                'label' => 'Colonnes',
                                                'name' => 'columns',
                                                'type' => 'select',
                                                'choices' => ['2' => '2', '3' => '3', '4' => '4'],
                                                'default_value' => '3',
                                            ],
                                            [
                                                'key' => 'field_cards_grid_cards',
                                                'label' => 'Cartes',
                                                'name' => 'cards',
                                                'type' => 'repeater',
                                                'button_label' => 'Ajouter une carte',
                                                'sub_fields' => [
                                                    [
                                                        'key' => 'field_card_title',
                                                        'label' => 'Titre',
                                                        'name' => 'title',
                                                        'type' => 'text',
                                                    ],
                                                    [
                                                        'key' => 'field_card_content',
                                                        'label' => 'Contenu',
                                                        'name' => 'content',
                                                        'type' => 'textarea',
                                                        'rows' => 2,
                                                    ],
                                                    [
                                                        'key' => 'field_card_image',
                                                        'label' => 'Image',
                                                        'name' => 'image',
                                                        'type' => 'image',
                                                        'return_format' => 'array',
                                                    ],
                                                    [
                                                        'key' => 'field_card_url',
                                                        'label' => 'Lien',
                                                        'name' => 'url',
                                                        'type' => 'text',
                                                        'instructions' => $url_field_instructions,
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                    'layout_cta_banner' => [
                                        'key' => 'layout_cta_banner',
                                        'name' => 'cta_banner',
                                        'label' => 'Bandeau CTA',
                                        'display' => 'block',
                                        'sub_fields' => [
                                            [
                                                'key' => 'field_cta_banner_title',
                                                'label' => 'Titre',
                                                'name' => 'title',
                                                'type' => 'text',
                                                'required' => 1,
                                            ],
                                            [
                                                'key' => 'field_cta_banner_text',
                                                'label' => 'Texte',
                                                'name' => 'text',
                                                'type' => 'textarea',
                                                'rows' => 2,
                                            ],
                                            [
                                                'key' => 'field_cta_banner_label',
                                                'label' => 'Libellé du bouton',
                                                'name' => 'cta_label',
                                                'type' => 'text',
                                                'required' => 1,
                                            ],
                                            [
                                                'key' => 'field_cta_banner_url',
                                                'label' => 'URL du bouton',
                                                'name' => 'cta_url',
                                                'type' => 'text',
                                                'instructions' => $url_field_instructions,
                                                'required' => 1,
                                            ],
                                        ],
                                    ],
                                    'layout_accordion' => [
                                        'key' => 'layout_accordion',
                                        'name' => 'accordion',
                                        'label' => 'Accordéon',
                                        'display' => 'block',
                                        'sub_fields' => [
                                            [
                                                'key' => 'field_accordion_items',
                                                'label' => 'Items',
                                                'name' => 'items',
                                                'type' => 'repeater',
                                                'button_label' => 'Ajouter un item',
                                                'sub_fields' => [
                                                    [
                                                        'key' => 'field_accordion_item_question',
                                                        'label' => 'Question',
                                                        'name' => 'question',
                                                        'type' => 'text',
                                                        'required' => 1,
                                                    ],
                                                    [
                                                        'key' => 'field_accordion_item_answer',
                                                        'label' => 'Réponse',
                                                        'name' => 'answer',
                                                        'type' => 'wysiwyg',
                                                        'tabs' => 'visual',
                                                        'media_upload' => 0,
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                    'layout_embed' => [
                                        'key' => 'layout_embed',
                                        'name' => 'embed',
                                        'label' => 'Contenu tiers (embed)',
                                        'display' => 'block',
                                        'sub_fields' => [
                                            [
                                                'key' => 'field_embed_code',
                                                'label' => "Code d'intégration",
                                                'name' => 'code',
                                                'type' => 'textarea',
                                                'rows' => 4,
                                                'instructions' => 'Code fourni par le service tiers (iframe vidéo, embed réseau social...).',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'page',
                ],
            ],
        ],
    ]);
});
