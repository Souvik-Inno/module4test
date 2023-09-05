<?php

namespace Drupal\news_api\Plugin\rest\resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\statistics\NodeStatisticsDatabaseStorage;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a Resource for getting student data.
 *
 * @RestResource(
 *   id = "news_resource",
 *   label = @Translation("News Resource"),
 *   uri_paths = {
 *     "canonical" = "/expose_api/news_resource"
 *   }
 * )
 */
class NewsResource extends ResourceBase {

  /**
   * Connects to the entity.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Stores the statistics values.
   *
   * @var \Drupal\statistics\NodeStatisticsDatabaseStorage
   */
  protected $statisticsStorage;

  /**
   * Gets value from config.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs an object of the class.
   */
  public function __construct(
      array $configuration,
      $plugin_id,
      $plugin_definition,
      array $serializer_formats,
      LoggerInterface $logger,
      EntityTypeManager $entity_type_manager,
      NodeStatisticsDatabaseStorage $statistics_storage,
      ConfigFactoryInterface $config_factory
    ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->entityTypeManager = $entity_type_manager;
    $this->statisticsStorage = $statistics_storage;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('entity_type.manager'),
      $container->get('statistics.storage.node'),
      $container->get('config.factory')
    );
  }

  /**
   * Responds to entity GET requests.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request to get the parameters from url.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Response to send student data.
   */
  public function get(Request $request) {
    if ($tags = $request->query->get('tags')) {
      $terms = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadByProperties(['name' => explode(' ', $tags)]);
      if (!empty($terms)) {
        $response = new ResourceResponse($this->getNewsDetails($terms));
        $cacheable_metadata = new CacheableMetadata();
        $cacheable_metadata->addCacheTags(['node_list:news']);
        $cacheable_metadata->addCacheContexts(['url']);
        $response->addCacheableDependency($cacheable_metadata);
        $config = $this->configFactory->get('news_api.settings');
        $response->headers->set('secret_key', $config->get('secret_key'));
        return $response;
      }
    }
    $response = new ResourceResponse([
      'error' => 'No news for the Tag was found.',
    ]);
    return $response;
  }

  /**
   * Returns details for news after querying.
   *
   * @param array $terms
   *   The terms to be passed to get news details.
   *
   * @return array
   *   The news details as an array.
   */
  private function getNewsDetails(array $terms = []) {
    try {
      $news_ids = $this->entityTypeManager->getStorage('node')->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', 'news');
      foreach ($terms as $term) {
        $news_ids = $news_ids->condition(
          'field_tags.target_id',
          $term->get('tid')->value,
          'IN',
        );
      }
      $news_ids = $news_ids->execute();
      $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($news_ids);
      $node_details = [];
      foreach ($nodes as $record) {
        $tags = $this->entityTypeManager->getStorage('taxonomy_term')
          ->load($record->get('field_tags')->target_id);
        $view_count = $this->statisticsStorage->fetchView($record->id())->getDayCount();
        $image_values = [];
        $images = $record->get('field_images');
        foreach ($images as $image) {
          $image_values[] = [
            'target_id' => $image->target_id,
            'alt_text' => $image->alt,
            'width' => $image->width,
            'height' => $image->height,
          ];
        }
        $node_details[] = [
          'title' => $record->get('title')->value,
          'body' => $record->get('body')->value,
          'summary' => $record->get('body')->summary,
          'tags' => $tags->get('name')->value,
          'images' => $image_values,
          'views' => $view_count,
          'Published Date' => $record->get('field_published_date')->value,
        ];
      }
      return $node_details;
    }
    catch (\Exception $e) {
      return [
        'error' => $e->getMessage(),
      ];
    }
  }

}
