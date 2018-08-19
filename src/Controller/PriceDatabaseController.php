<?php

/**
 * @file
 * Contains \Drupal\site_price\Controller\PriceDatabaseController.
 */

namespace Drupal\site_price\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Database class.
 */
class PriceDatabaseController extends ControllerBase {

  /**
   * The database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new PriceDatabaseController.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Загружает тип прайс-листа.
   */
  public function loadPrice($prid) {
    $query = $this->connection->select('site_price', 'n');
    $query->fields('n');
    $query->condition('n.prid', $prid);
    return $query->execute()->fetchObject();
  }

  /**
   *  Сохраняет тип прайс-листа.
   */
  public function insertPrice($entry) {
    $insert = $this->connection->insert('site_price')->fields($entry);
    return $insert->execute();
  }

  /**
   *  Обновляет тип прайс-листа.
   */
  public function updatePrice($entry) {
    $update = $this->connection->update('site_price')
      ->fields($entry)
      ->condition('prid', $entry['prid']);
    return $update->execute();
  }

  /**
   * Формирует массив с именами и идентификаторами всех прайс-листов.
   */
  public function loadPriceLists() {
    $query = $this->connection->select('site_price', 'n');
    $query->fields('n');
    $result = $query->execute();

    $prices = [];

    foreach ($result as $row) {
      $prices += array($row->prid => $row->title);
    }

    return $prices;
  }

  /**
   *  Удаляет прайс-лист.
   */
  public function deletePrice($prid) {
    // Выбираем все группы, которые связаны с текущим прайс-листом.
    // Удаляем группы.
    $query = $this->connection->select('site_price_groups', 'n');
    $query->fields('n');
    $query->condition('prid', $prid);
    $groups = $query->execute();
    foreach ($groups as $group) {
      $this->deletePriceGroup($group->gid);
    }

    // Удаляет запись о прайс-листе.
    $delete = $this->connection->delete('site_price')
      ->condition('prid', $prid)
      ->execute();

    return $delete;
  }

  /**
   * Загружает тип ценовой группы.
   */
  public function loadPriceGroup($gid) {
    $query = $this->connection->select('site_price_groups', 'n');
    $query->fields('n');
    $query->condition('n.gid', $gid);
    return $query->execute()->fetchObject();
  }

  /**
   *  Обновляет тип ценовой группы в базе данных.
   */
  public function updatePriceGroup($entry) {
    $update = $this->connection->update('site_price_groups')
      ->fields($entry)
      ->condition('gid', $entry['gid']);
    return $update->execute();
  }

  /**
   *  Сохраняет тип ценовой группы в базе данных.
   */
  public function insertPriceGroup($entry) {
    $insert = $this->connection->insert('site_price_groups')->fields($entry);
    return $insert->execute();
  }

  /**
   *  Удаляет ценовую группу в базе данных.
   */
  public function deletePriceGroup($gid) {
    // Удаляем категории.
    $query = $this->connection->select('site_price_categories', 'n');
    $query->fields('n');
    $query->condition('gid', $gid);
    $categories = $query->execute();
    foreach ($categories as $category) {
      $this->deletePriceCategory($category->cid);
    }

    $return_value = $this->connection->delete('site_price_groups')
      ->condition('gid', $gid)
      ->execute();

    $return_value = $this->connection->delete('site_price_hierarchy')
      ->condition('gid', $gid)
      ->execute();

    return $return_value;
  }

  /**
   *  Обновляет вес позиций привязанных к группе прайс-листа.
   */
  public function groupPositionSetWeight($entry) {
    $update = $this->connection->update('site_price_hierarchy')
      ->fields(array('weight' => $entry['weight']))
      ->condition('gid', $entry['gid'])
      ->condition('pid', $entry['pid']);

    return $update->execute();
  }

  /**
   * Формирует массив с именами и идентификаторами всех ценовых групп.
   */
  public function loadPriceGroups() {
    $query = $this->connection->select('site_price_groups', 'n');
    $query->fields('n');
    $result = $query->execute();

    $price_groups = [];

    foreach ($result as $row) {
      $price_groups += array($row->gid => $row->title);
    }

    return $price_groups;
  }

  /**
   * Загружает категорию прайс-листа.
   */
  public function loadPriceCategory($cid) {
    $query = $this->connection->select('site_price_categories', 'n');
    $query->fields('n');
    $query->condition('n.cid', $cid);
    return $query->execute()->fetchObject();
  }

  /**
   *  Сохраняет категорию прайс-листа в базе данных.
   */
  public function insertPriceCategory($entry) {
    $insert = $this->connection->insert('site_price_categories')->fields($entry);
    return $insert->execute();
  }

  /**
   *  Обновляет категорию прайс-листа в базе данных.
   */
  public function updatePriceCategory($entry) {
    $update = $this->connection->update('site_price_categories')
      ->fields($entry)
      ->condition('cid', $entry['cid']);

    return $update->execute();
  }

  /**
   *  Удаляет категорию в базе данных.
   */
  public function deletePriceCategory($cid) {
    $return_value = $this->connection->delete('site_price_hierarchy')
      ->condition('cid', $cid)
      ->execute();

    $return_value = $this->connection->delete('site_price_categories')
      ->condition('cid', $cid)
      ->execute();

    return $return_value;
  }

  /**
   *  Обновляет вес позиций привязанных к категории прайс-листа.
   */
  public function categoryPositionSetWeight($entry) {
    $update = $this->connection->update('site_price_hierarchy')
      ->fields(array('weight' => $entry['weight']))
      ->condition('cid', $entry['cid'])
      ->condition('pid', $entry['pid']);

    return $update->execute();
  }

  /**
   * Загружает позицию прайс-листа.
   */
  public function loadPricePosition($pid) {
    $query = $this->connection->select('site_price_positions', 'n');
    $query->fields('n');
    $query->condition('n.pid', $pid);
    return $query->execute()->fetchObject();
  }

  /**
   * Загружает позицию прайс-листа по названию.
   */
  public function loadPricePositionByTitle($title) {
    $query = $this->connection->select('site_price_positions', 'n');
    $query->fields('n');
    $query->condition('n.title', $title);
    return $query->execute()->fetchObject();
  }

  /**
   *  Сохраняет позицию прайс-листа в базе данных.
   */
  public function insertPricePosition($entry) {
    $insert = $this->connection->insert('site_price_positions')->fields($entry);
    return $insert->execute();
  }

  /**
   *  Обновляет позицию прайс-листа в базе данных.
   */
  public function updatePricePosition($entry) {
    $update = $this->connection->update('site_price_positions')
      ->fields($entry)
      ->condition('pid', $entry['pid']);

    return $update->execute();
  }

  /**
   *  Удаляет позицию в базе данных.
   */
  public function deletePricePosition($pid) {
    $return_value = $this->connection->delete('site_price_hierarchy')
      ->condition('pid', $pid)
      ->execute();

    $return_value = $this->connection->delete('site_price_positions')
      ->condition('pid', $pid)
      ->execute();

    return $return_value;
  }

  /**
   *  Сохраняет связь между позицией и группой или позицией и категорией.
   */
  public function insertPriceHierarchy($entry) {
    $insert = $this->connection->insert('site_price_hierarchy')->fields($entry);

    return $insert->execute();
  }

  /**
   *  Удаляет позицию из состава.
   */
  public function deletePositionFromContent($pid, $id, $type) {
    if ($type == 'group') {
      $delete = $this->connection->delete('site_price_hierarchy')->condition('pid', $pid)->condition('gid', $id);
    }

    if ($type == 'category') {
      $delete = $this->connection->delete('site_price_hierarchy')->condition('pid', $pid)->condition('cid', $id);
    }

    return $delete->execute();
  }
}
