<?php

namespace Drupal\omise_payment\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Omise customer entity.
 *
 * @ingroup omise_payment
 *
 * @ContentEntityType(
 *   id = "omise_customer",
 *   label = @Translation("Omise customer"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\omise_payment\OmiseCustomerListBuilder",
 *     "views_data" = "Drupal\omise_payment\Entity\OmiseCustomerViewsData",
 *     "translation" = "Drupal\omise_payment\OmiseCustomerTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\omise_payment\Form\OmiseCustomerForm",
 *       "add" = "Drupal\omise_payment\Form\OmiseCustomerForm",
 *       "edit" = "Drupal\omise_payment\Form\OmiseCustomerForm",
 *       "delete" = "Drupal\omise_payment\Form\OmiseCustomerDeleteForm",
 *     },
 *     "access" = "Drupal\omise_payment\OmiseCustomerAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\omise_payment\OmiseCustomerHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "omise_customer",
 *   data_table = "omise_customer_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer omise customer entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "omise_id",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/omise_customer/{omise_customer}",
 *     "add-form" = "/admin/structure/omise_customer/add",
 *     "edit-form" = "/admin/structure/omise_customer/{omise_customer}/edit",
 *     "delete-form" = "/admin/structure/omise_customer/{omise_customer}/delete",
 *     "collection" = "/admin/structure/omise_customer",
 *   },
 *   field_ui_base_route = "omise_customer.settings"
 * )
 */
class OmiseCustomer extends ContentEntityBase implements OmiseCustomerInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOmiseID() {
    return $this->get('omise_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setOmiseID($id) {
    $this->set('omise_id', $id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Omise customer entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['omise_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Omise ID'))
      ->setDescription(t('The ID of the Omise customer entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Omise customer is published.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
