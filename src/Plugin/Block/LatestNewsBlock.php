<?php

namespace Drupal\latest_news\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Latest News' block.
 *
 * @Block(
 *   id = "latest_news_block",
 *   admin_label = @Translation("Latest News Block"),
 *   category = @Translation("Custom Blocks")
 * )
 */
class LatestNewsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new LatestNewsBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $build = [];

    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'article')
      ->sort('created', 'DESC')
      ->range(0, 3);
      ->accessCheck(TRUE); 
    $nids = $query->execute();
    $articles = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

    $items = [];
    $cache_tags = [];

    foreach ($articles as $article) {
      $items[] = [
        'title' => $article->getTitle(),
        'teaser' => $article->get('body')->summary,
        'url' => $article->toUrl()->toString(),
      ];
      $cache_tags[] = 'node:' . $article->id();
    }
    $build['#cache']['tags'] = $cache_tags;
    $build['#theme'] = 'block__latest_news_block';
    $build['#items'] = $items;

    return $build;
  }
}
