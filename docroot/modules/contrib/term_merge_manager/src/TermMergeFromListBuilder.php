<?php

namespace Drupal\term_merge_manager;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Term merge from entities.
 *
 * @ingroup term_merge_manager
 */
class TermMergeFromListBuilder extends EntityListBuilder {

  public function load() {

    $entity_query = \Drupal::entityQuery('term_merge_from');
    $header = $this->buildHeader();

    $entity_query->pager(50);
    $entity_query->tableSort($header);

    $uids = $entity_query->execute();

    return $this->storage->loadMultiple($uids);
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {

    $header['id'] = array(
      'data' => $this->t('Term merge from ID'),
      'field' => 'id',
      'specifier' => 'id',
    );

    $header['vid'] = array(
      'data' => $this->t('Vocabulary'),
      'field' => 'vid',
      'specifier' => 'vid',
    );

    $header['name'] = array(
      'data' => $this->t('Name'),
      'field' => 'name',
      'specifier' => 'name',
    );

    $header['into'] = array(
      'data' => $this->t('Into'),
      'field' => 'tmiid',
      'specifier' => 'tmiid',
    );
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\term_merge_manager\Entity\TermMergeFrom */
    $row['id'] = $entity->id();
    $row['vid'] = $entity->getVid();
    $row['name'] = $entity->label();
    $row['into'] = Link::createFromRoute(
      $entity->getIntoId() . ' ('.$entity->getIntoName().')',
      'entity.taxonomy_term.edit_form',
      ['taxonomy_term' => $entity->getIntoId()]
    );
    return $row + parent::buildRow($entity);
  }

}
