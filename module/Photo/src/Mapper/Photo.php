<?php

namespace Photo\Mapper;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Photo\Model\MemberAlbum;
use Photo\Model\Photo as PhotoModel;

/**
 * Mappers for Photo.
 */
class Photo
{
    /**
     * Doctrine entity manager.
     *
     * @var EntityManager
     */
    protected $em;

    /**
     * Constructor.
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Returns all the photos in an album.
     *
     * @param \Photo\Model\Album $album The album to retrieve the photos
     *                                       from
     * @param int $start the result to start at
     * @param int $maxResults max amount of results to return,
     *                                       null for infinite
     *
     * @return array of photo's
     */
    public function getAlbumPhotos(
        \Photo\Model\Album $album,
        $start = 0,
        $maxResults = null
    ) {
        $qb = $this->em->createQueryBuilder();

        $qb->select('a')
            ->from('Photo\Model\Photo', 'a');
        if ($album instanceof MemberAlbum) {
            $qb->innerJoin('a.tags', 't')
                ->where('t.member = ?1')
                ->setParameter(1, $album->getMember());
            // We want to display the photos in a member's album in reversed
            // chronological order
            $qb->setFirstResult($start)
                ->orderBy('a.dateTime', 'DESC');
        } else {
            $qb->where('a.album = ?1')
                ->setParameter(1, $album);
            $qb->setFirstResult($start)
                ->orderBy('a.dateTime', 'ASC');
        }
        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Retrieves some random photos from the specified album. If the amount of
     * available photos is smaller than the requested count, less photos
     * will be returned.
     *
     * @param \Photo\Model\Album|int $album
     * @param int $maxResults
     *
     * @return array of Photo\Model\Photo
     */
    public function getRandomAlbumPhotos($album, $maxResults)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('a')
            ->from('Photo\Model\Photo', 'a')
            ->where('a.album = ?1')
            ->setParameter(1, $album)
            ->addSelect('RAND() as HIDDEN rand')
            ->orderBy('rand');
        $qb->setMaxResults($maxResults);

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns the next photo in the album to display.
     *
     * @return PhotoModel|null Photo if there is a next
     *                         photo, null otherwise
     */
    public function getNextPhoto(
        PhotoModel $photo,
        \Photo\Model\Album $album
    ) {
        $qb = $this->em->createQueryBuilder();

        $qb->select('a')
            ->from('Photo\Model\Photo', 'a');
        if ($album instanceof MemberAlbum) {
            $qb->innerJoin('a.tags', 't')
                ->where('t.member = ?1 AND a.dateTime > ?2')
                ->setParameter(1, $album->getMember())
                ->setParameter(2, $photo->getDateTime());
        } else {
            $qb->where('a.dateTime > ?1 AND a.album = ?2')
                ->setParameter(1, $photo->getDateTime())
                ->setParameter(2, $photo->getAlbum());
        }

        $qb->orderBy('a.dateTime', 'ASC')
            ->setMaxResults(1);
        $res = $qb->getQuery()->getResult();

        return empty($res) ? null : $res[0];
    }

    /**
     * Returns the previous photo in the album to display.
     *
     * @return PhotoModel|null Photo if there is a previous
     *                         photo, null otherwise
     */
    public function getPreviousPhoto(
        PhotoModel $photo,
        \Photo\Model\Album $album
    ) {
        $qb = $this->em->createQueryBuilder();

        $qb->select('a')
            ->from('Photo\Model\Photo', 'a');
        if ($album instanceof MemberAlbum) {
            $qb->innerJoin('a.tags', 't')
                ->where('t.member = ?1 AND a.dateTime < ?2')
                ->setParameter(1, $album->getMember())
                ->setParameter(2, $photo->getDateTime());
        } else {
            $qb->where('a.dateTime < ?1 AND a.album = ?2')
                ->setParameter(1, $photo->getDateTime())
                ->setParameter(2, $photo->getAlbum());
        }

        $qb->orderBy('a.dateTime', 'DESC')
            ->setMaxResults(1);
        $res = $qb->getQuery()->getResult();

        return empty($res) ? null : $res[0];
    }

    /**
     * Checks if the specified photo exists in the database already and returns
     * it if it does.
     *
     * @param string $path The storage path of the photo
     * @param \Photo\Model\Album $album the album the photo is in
     *
     * @return PhotoModel|null
     */
    public function getPhotoByData($path, $album)
    {
        return $this->getRepository()->findOneBy(
            [
                'path' => $path,
                'album' => $album->getId(),
            ]
        );
    }

    /**
     * Get the repository for this mapper.
     *
     * @return EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Photo\Model\Photo');
    }

    /**
     * Retrieves a photo by id from the database.
     *
     * @param int $photoId the id of the photo
     *
     * @return PhotoModel
     */
    public function getPhotoById($photoId)
    {
        return $this->getRepository()->find($photoId);
    }

    /**
     * Removes a photo.
     */
    public function remove(PhotoModel $photo)
    {
        $this->em->remove($photo);
    }

    /**
     * Persist photo.
     */
    public function persist(PhotoModel $photo)
    {
        $this->em->persist($photo);
    }

    /**
     * Flush.
     */
    public function flush()
    {
        $this->em->flush();
    }

    /**
     * Get the entity manager connection.
     *
     * @return Connection
     */
    public function getConnection()
    {
        return $this->em->getConnection();
    }
}
