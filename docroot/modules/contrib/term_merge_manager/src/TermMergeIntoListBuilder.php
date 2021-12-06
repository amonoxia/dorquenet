<?php

namespace Drupal\term_merge_manager;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Term merge into entities.
 *
 * @ingroup term_merge_manager
 */
class TermMergeIntoListBuilder extends EntityListBuilder {

  public function load() {

    $entity_query = \Drupal::entityQuery('term_merge_into');
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
      'data' => $this->t('Term merge into ID'),
      'field' => 'id',
      'specifier' => 'id',
    );

    $header['vid'] = array(
      'data' => $this->t('Vocabulary'),
      'field' => 'vid',
      'specifier' => 'vid',
    );

    $header['term'] = array(
      'data' => $this->t('Term'),
      'field' => 'tid',
      'specifier' => 'tid',
    );
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\term_merge_manager\Entity\TermMergeInto */
    $row['id'] = $entity->id();
    $row['vid'] = $entity->getVid();
    $row['tid'] = Link::createFromRoute(
      $entity->getTid().' ('.$entity->getName().')',
      'entity.taxonomy_term.edit_form',
      ['taxonomy_term' => $entity->getTid()]
    );
    return $row + parent::buildRow($entity);
  }

}
