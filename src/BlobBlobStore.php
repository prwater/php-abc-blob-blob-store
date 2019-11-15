<?php
declare(strict_types=1);

namespace Plaisio\BlobStore;

use Plaisio\Kernel\Nub;
use SetBased\Helper\ProgramExecution;

/**
 * Class for storing BLOBs (i.e. files, documents, images, data) as BLOBs in the database.
 */
class BlobBlobStore implements BlobStore
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Path to the file executable.
   *
   * @var string
   */
  public static $filePath = '/usr/bin/file';

  /**
   * Directory for storing temporary files. If null sys_get_temp_dir() will be used.
   *
   * @var ?string
   */
  public static $tmpDir = null;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function delBlob(int $blbId): void
  {
    Nub::$DL->abcBlobDelBlob(Nub::$companyResolver->getCmpId(), $blbId);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function getBlob(int $blbId): array
  {
    return Nub::$DL->abcBlobGetBlob(Nub::$companyResolver->getCmpId(), $blbId);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function getMetadata(int $blbId): array
  {
    return Nub::$DL->abcBlobGetMetadata(Nub::$companyResolver->getCmpId(), $blbId);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function mimeTypePath(string $path): string
  {
    list($output) = ProgramExecution::exec1([self::$filePath, '-ib', $path], [0], true);

    $mimeType = $output[0];

    return $mimeType;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function mimeTypeString(string $data): string
  {
    $path   = tempnam(static::$tmpDir ?? sys_get_temp_dir(), 'mime-');
    $handle = fopen($path, 'wb');
    fwrite($handle, $data);
    fclose($handle);

    try
    {
      $mimeType = $this->mimeTypePath($path);
    }
    finally
    {
      unlink($path);
    }

    return $mimeType;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function putFile(string $path, string $filename, ?string $mimeType = null, ?string $timestamp = null): int
  {
    if ($mimeType===null)
    {
      $mimeType = $this->mimeTypePath($path);
    }

    $data = file_get_contents($path);

    Nub::$DL->abcBlobInsertBlob(Nub::$companyResolver->getCmpId(), $filename, $mimeType, $timestamp, $data);

    return Nub::$DL->abcBlobWorkaround();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function putString(string $data, string $filename, ?string $mimeType = null, ?string $timestamp = null): int
  {
    if ($mimeType===null)
    {
      $mimeType = $this->mimeTypeString($data);
    }

    Nub::$DL->abcBlobInsertBlob(Nub::$companyResolver->getCmpId(), $filename, $mimeType, $timestamp, $data);

    return Nub::$DL->abcBlobWorkaround();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function searchByMd5(string $md5): array
  {
    return Nub::$DL->abcBlobGetMetadataByMd5(Nub::$companyResolver->getCmpId(), $md5);
  }
}

//----------------------------------------------------------------------------------------------------------------------