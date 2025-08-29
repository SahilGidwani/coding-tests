<?php

namespace Drupal\movie_ratings\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\movie_ratings\QRCodeService;

/**
 * Provides a Trailer QR Code Block.
 *
 * @Block(
 *   id = "trailer_qr_block",
 *   admin_label = @Translation("Trailer QR Code"),
 *   category = @Translation("Movie Ratings")
 * )
 */
class TrailerQrBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The QR code service.
   *
   * @var \Drupal\movie_ratings\QRCodeService
   */
  protected $qrCodeService;

  /**
   * Constructs a new TrailerQrBlock instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, QRCodeService $qr_code_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->qrCodeService = $qr_code_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('movie_ratings.qr_code')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $currentNode = $this->routeMatch->getParameter('node');

    // Check if the current node is a movie content type.
    if (!$currentNode || $currentNode->bundle() !== 'movies') {
      return [];
    }

    // Check if movie has trailer URL.
    if (!$currentNode->hasField('field_trailer_url') || $currentNode->get('field_trailer_url')->isEmpty()) {
      return [];
    }

    $movieId = $currentNode->id();
    $trailerUrl = $currentNode->get('field_trailer_url')->first()->getUrl()->toString();

    // Validate YouTube URL.
    if (!$this->qrCodeService->isYouTubeUrl($trailerUrl)) {
      return [
        '#markup' => '<p class="trailer-error">' .
        $this->t('Invalid trailer URL. Please provide a valid YouTube link.') .
        '</p>',
      ];
    }

    // Generate QR code.
    $qrFilename = 'movie_' . $movieId . '_trailer';
    $qrCodeUrl = $this->qrCodeService->generateQrCode($trailerUrl, $qrFilename);

    if (!$qrCodeUrl) {
      return [
        '#markup' => '<p class="qr-error">' .
        $this->t('Unable to generate QR code at this time.') .
        '</p>',
      ];
    }

    return [
      '#theme' => 'trailer_qr_block',
      '#movie_title' => $currentNode->getTitle(),
      '#qr_code_url' => $qrCodeUrl,
      '#attached' => [
        'library' => ['movie_ratings/rating_styles'],
      ],
      '#cache' => [
        'tags' => [
          "node:{$movieId}",
          'trailer_qr',
        ],
        'max-age' => 86400,
      ],
    ];
  }

}
