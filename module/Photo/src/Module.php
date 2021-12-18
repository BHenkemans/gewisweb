<?php

namespace Photo;

use Doctrine\Laminas\Hydrator\DoctrineObject;
use Doctrine\ORM\Events;
use Exception;
use Laminas\Cache\StorageFactory;
use Laminas\Mvc\MvcEvent;
use Interop\Container\ContainerInterface;
use League\Glide\Urls\UrlBuilderFactory;
use Photo\Command\WeeklyPhoto;
use Photo\Form\{
    CreateAlbum as CreateAlbumForm,
    EditAlbum as EditAlbumForm,
};
use Photo\Listener\{
    AlbumDate as AlbumDateListener,
    Remove as RemoveListener,
};
use Photo\Service\{
    Admin as AdminService,
    Album as AlbumService,
    AlbumCover as AlbumCoverService,
    Metadata as MetadataService,
    Photo as PhotoService,
};
use Photo\View\Helper\GlideUrl;
use User\Authorization\AclServiceFactory;

class Module
{
    public function onBootstrap(MvcEvent $e)
    {
        $container = $e->getApplication()->getServiceManager();
        $em = $container->get('doctrine.entitymanager.orm_default');
        $dem = $em->getEventManager();
        $dem->addEventListener([Events::prePersist], new AlbumDateListener());
        $photoService = $container->get('photo_service_photo');
        $albumService = $container->get('photo_service_album');
        $dem->addEventListener([Events::preRemove], new RemoveListener($photoService, $albumService));
    }

    /**
     * Get the configuration for this module.
     *
     * @return array Module configuration
     */
    public function getConfig(): array
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    /**
     * Get service configuration.
     *
     * @return array Service configuration
     */
    public function getServiceConfig(): array
    {
        return [
            'factories' => [
                'photo_service_album' => function (ContainerInterface $container) {
                    $photoService = $container->get('photo_service_photo');
                    $albumCoverService = $container->get('photo_service_album_cover');
                    $memberService = $container->get('decision_service_member');
                    $storageService = $container->get('application_service_storage');
                    $albumMapper = $container->get('photo_mapper_album');
                    $createAlbumForm = $container->get('photo_form_album_create');
                    $editAlbumForm = $container->get('photo_form_album_edit');
                    $aclService = $container->get('photo_service_acl');
                    $translator = $container->get('translator');

                    return new AlbumService(
                        $photoService,
                        $albumCoverService,
                        $memberService,
                        $storageService,
                        $albumMapper,
                        $createAlbumForm,
                        $editAlbumForm,
                        $aclService,
                        $translator,
                    );
                },
                'photo_service_metadata' => function () {
                    return new MetadataService();
                },
                'photo_service_photo' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $memberService = $container->get('decision_service_member');
                    $storageService = $container->get('application_service_storage');
                    $photoMapper = $container->get('photo_mapper_photo');
                    $tagMapper = $container->get('photo_mapper_tag');
                    $voteMapper = $container->get('photo_mapper_vote');
                    $weeklyPhotoMapper = $container->get('photo_mapper_weekly_photo');
                    $profilePhotoMapper = $container->get('photo_mapper_profile_photo');
                    $photoConfig = $container->get('config')['photo'];
                    $aclService = $container->get('photo_service_acl');

                    return new PhotoService(
                        $translator,
                        $memberService,
                        $storageService,
                        $photoMapper,
                        $tagMapper,
                        $voteMapper,
                        $weeklyPhotoMapper,
                        $profilePhotoMapper,
                        $photoConfig,
                        $aclService
                    );
                },
                'photo_service_album_cover' => function (ContainerInterface $container) {
                    $photoMapper = $container->get('photo_mapper_photo');
                    $albumMapper = $container->get('photo_mapper_album');
                    $storage = $container->get('application_service_storage');
                    $photoConfig = $container->get('config')['photo'];
                    $storageConfig = $container->get('config')['storage'];

                    return new AlbumCoverService(
                        $photoMapper,
                        $albumMapper,
                        $storage,
                        $photoConfig,
                        $storageConfig,
                    );
                },
                'photo_service_admin' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $photoService = $container->get('photo_service_photo');
                    $albumService = $container->get('photo_service_album');
                    $metadataService = $container->get('photo_service_metadata');
                    $storageService = $container->get('application_service_storage');
                    $photoMapper = $container->get('photo_mapper_photo');
                    $photoConfig = $container->get('config')['photo'];
                    $aclService = $container->get('photo_service_acl');

                    return new AdminService(
                        $translator,
                        $photoService,
                        $albumService,
                        $metadataService,
                        $storageService,
                        $photoMapper,
                        $photoConfig,
                        $aclService
                    );
                },
                'photo_form_album_edit' => function (ContainerInterface $container) {
                    $form = new EditAlbumForm(
                        $container->get('translator')
                    );
                    $form->setHydrator($container->get('photo_hydrator'));

                    return $form;
                },
                'photo_form_album_create' => function (ContainerInterface $container) {
                    $form = new CreateAlbumForm(
                        $container->get('translator')
                    );
                    $form->setHydrator($container->get('photo_hydrator'));

                    return $form;
                },
                'photo_hydrator' => function (ContainerInterface $container) {
                    return new DoctrineObject(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'photo_mapper_album' => function (ContainerInterface $container) {
                    return new Mapper\Album(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'photo_mapper_photo' => function (ContainerInterface $container) {
                    return new Mapper\Photo(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'photo_mapper_profile_photo' => function (ContainerInterface $container) {
                    return new Mapper\ProfilePhoto(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'photo_mapper_tag' => function (ContainerInterface $container) {
                    return new Mapper\Tag(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'photo_mapper_weekly_photo' => function (ContainerInterface $container) {
                    return new Mapper\WeeklyPhoto(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'photo_mapper_vote' => function (ContainerInterface $container) {
                    return new Mapper\Vote(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'photo_service_acl' => AclServiceFactory::class,
                'album_page_cache' => function () {
                    return StorageFactory::factory(
                        [
                            'adapter' => [
                                'name' => 'filesystem',
                                'options' => [
                                    'dirLevel' => 2,
                                    'cacheDir' => 'data/cache',
                                    'dirPermission' => 0755,
                                    'filePermission' => 0666,
                                    'namespaceSeparator' => '-db-',
                                ],
                            ],
                            'plugins' => ['serializer'],
                        ]
                    );
                },
                'activity_service_acl' => AclServiceFactory::class,
                WeeklyPhoto::class => function (ContainerInterface $container) {
                    $weeklyPhoto = new WeeklyPhoto();
                    $photoService = $container->get('photo_service_photo');
                    $weeklyPhoto->setPhotoService($photoService);
                    return $weeklyPhoto;
                },
            ],
        ];
    }

    /**
     * @return array
     */
    public function getViewHelperConfig(): array
    {
        return [
            'factories' => [
                'glideUrl' => function (ContainerInterface $container) {
                    $helper = new GlideUrl();
                    $config = $container->get('config');
                    if (
                        !isset($config['glide']) || !isset($config['glide']['base_url'])
                        || !isset($config['glide']['signing_key'])
                    ) {
                        throw new Exception('Invalid glide configuration');
                    }

                    $urlBuilder = UrlBuilderFactory::create(
                        $config['glide']['base_url'],
                        $config['glide']['signing_key']
                    );
                    $helper->setUrlBuilder($urlBuilder);

                    return $helper;
                },
            ],
        ];
    }
}
