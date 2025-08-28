<?php

namespace Drupal\movie_ratings;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;

/**
 * Service for generating QR codes.
 */
class QRCodeService {

  /**
   * File system service object.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Stream wrapper manager object.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * Logger object
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Constructs a QrCodeService object.
   */
  public function __construct(FileSystemInterface $file_system, StreamWrapperManagerInterface $stream_wrapper_manager, LoggerChannelFactoryInterface $logger) {
    $this->fileSystem = $file_system;
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->logger = $logger;
  }

  /**
   * Generate QR code for a URL.
   *
   * @param string $url
   * @param string $filename
   *
   * @return string|null
   */
  public function generateQrCode($url, $filename) {
    if (empty($url)) {
      return NULL;
    }

    $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);

    try {
      $builder = new Builder(
        writer: new PngWriter(),
        data: $url,
        encoding: new Encoding('UTF-8'),
        size: 200,
        margin: 10,
      );

      $result = $builder->build();

      // Prepare directory for uploading generated QR codes.
      $directory = 'public://qr-codes';

      // Check if directory is created.
      if (!$this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY)) {
        $this->logger->get('movie_ratings')->error('Failed to create QR code directory');
        return NULL;
      }

      // Save generated QR code image
      $file_uri = $directory . '/' . $filename . '.png';

      // Check if file is saved.
      if (!$this->fileSystem->saveData($result->getString(), $file_uri, FileSystemInterface::EXISTS_REPLACE)) {
        $this->logger->get('movie_ratings')->error('Failed to save QR code file');
        return NULL;
      }

      // Return the url of QR Code.
      return $this->streamWrapperManager->getViaUri($file_uri)->getExternalUrl();

    } catch (\Exception $e) {
      $this->logger->get('movie_ratings')->error('QR code generation failed: @error', ['@error' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Check if a URL is a valid YouTube URL.
   *
   * @param string $url
   *   The URL to validate.
   *
   * @return bool
   *   TRUE if valid YouTube URL, FALSE otherwise.
   */
  public function isYouTubeUrl($url) {
    $youtube_patterns = [
      '/^https?:\/\/(www\.)?youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/',
      '/^https?:\/\/youtu\.be\/([a-zA-Z0-9_-]+)/',
      '/^https?:\/\/(www\.)?youtube\.com\/embed\/([a-zA-Z0-9_-]+)/',
    ];

    foreach ($youtube_patterns as $pattern) {
      if (preg_match($pattern, $url)) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
