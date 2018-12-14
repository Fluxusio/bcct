<?php

namespace Drupal\omise_payment\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;

/**
 * Plugin implementation of the 'omise_customer_type' field type.
 *
 * @FieldType(
 *   id = "omise_customer_type",
 *   label = @Translation("Omise customer with default card"),
 *   description = @Translation("Omise Customer with default card"),
 *   default_widget = "omise_customer_widget",
 *   default_formatter = "omise_customer_formatter",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList",
 * )
 */
class OmiseCustomerType extends EntityReferenceItem
{

    /**
     * Property definition extensions for adding extra text field
     * @param FieldStorageDefinitionInterface $field_definition
     * @return mixed
     */
    public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition)
    {
        $properties = parent::propertyDefinitions($field_definition);
        $restaurant_definition = DataDefinition::create('string')
          ->setLabel(new TranslatableMarkup('Default card'))
          ->setDescription(new TranslatableMarkup('Set the token card to use by default'))
          ->setRequired(false);
        $properties['omise_default_card'] = $restaurant_definition;
        return $properties;
    }

    public static function schema(FieldStorageDefinitionInterface $field_definition)
    {
        $schema = parent::schema($field_definition);
        $schema['columns']['omise_default_card'] = array(
          'type' => 'varchar',
          'length' => '255',
          'not null' => false
        );

        return $schema;
    }

    /**
     * {@inheritdoc}
     */
    public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data)
    {
        $element['target_type'] = [
          '#type' => 'select',
          '#title' => t('Type of item to reference'),
          '#options' => ['omise_customer' => 'Omise Customer'],
          '#default_value' => $this->getSetting('target_type'),
          '#required' => true,
          '#disabled' => $has_data,
          '#size' => 1,
        ];

        return $element;
    }
}