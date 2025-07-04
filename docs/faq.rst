Frequently Asked Questions (FAQ)
================================

Sharing parameter configuration
-------------------------------

Q: I use the same value in multiple endpoints. How can I avoid duplicating the descriptions?

A: You can configure ``schemas`` in the nelmio_api_doc configuration and then reference them:

.. code-block:: yaml

    # config/nelmio_api_doc.yaml
    nelmio_api_doc:
        documentation:
            components:
                schemas:
                    NelmioImageList:
                        description: "Response for some queries"
                        type: object
                        properties:
                            total:
                                type: integer
                                example: 42
                            items:
                                type: array
                                items:
                                    $ref: "#/components/schemas/ImageMetadata"

.. code-block:: php

    // src/App/Controller/NelmioController.php
    /**
     * @OA\Response(
     *     response=200,
     *     description="List of image definitions",
     *     @OA\JsonContent(
     *       ref="#/components/schemas/NelmioImageList",
     *     )
     */

Optional Path Parameters
------------------------

Q: I have a controller with an optional path parameter. In swagger-ui, the parameter is required - can I make it
optional? The controller might look like this::

    /**
     * Get all user meta or metadata for a specific field.
     *
     * @Route("/{user}/meta/{metaName}",
     *     methods={"GET"},
     *     name="get_user_metadata"
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Json object with all user meta data or a json string with the value of the requested field"
     * )
     */
    public function getAction(string $user, string $metaName = null)
    {
        ...
    }

A: Optional path parameters are not supported by the OpenAPI specification. The solution to this is to define two
separate actions in your controller. For example::

    /**
     * Get all user meta data.
     *
     * @Route("/{user}/meta",
     *     methods={"GET"},
     *     name="get_user_metadata"
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Json hashmap with all user meta data",
     *     @OA\JsonContent(@OA\Schema(
     *        type="object",
     *        example={"foo": "bar", "hello": "world"}
     *     ))
     * )
     */
    public function cgetAction(string $user)
    {
        return $this->getAction($user, null);
    }

    /**
     * Get user meta for a specific field.
     *
     * @Route("/{user}/meta/{metaName}",
     *     methods={"GET"},
     *     name="get_user_metadata_single"
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="A json string with the value of the requested field",
     *     @OA\JsonContent(@OA\Schema(
     *          type="string"
     *     ))
     * )
     */
    public function getAction(string $user, string $metaName = null)
    {
        ...
    }

The first action is redundant for Symfony, but adds all the relevant documentation for the OpenAPI specification.

Asset files not loaded
----------------------

Q: How do I fix 404 or 406 HTTP status on NelmioApiDocBundle assets files (css, js, images)?

A: The assets normally are installed by composer if any command event (usually ``post-install-cmd`` or
``post-update-cmd``) triggers the ``ScriptHandler::installAssets`` script.
If you have not set up this script, you can manually execute this command:

.. code-block:: bash

    $ php bin/console assets:install --symlink

Re-add Google Fonts
-------------------

Q: How can I change the font for the UI?

A: We removed the google fonts in 3.3 to avoid the external request for GDPR reasons. To change the font, you can :doc:`customize the template <customization>` to add this style information:

.. code-block:: twig

    {# templates/bundles/NelmioApiDocBundle/SwaggerUi/index.html.twig #}
    {#
       To avoid a "reached nested level" error an exclamation mark `!` has to be added
       See https://symfony.com/blog/new-in-symfony-3-4-improved-the-overriding-of-templates
    #}
    {% extends '@!NelmioApiDoc/SwaggerUi/index.html.twig' %}

    {% block stylesheets %}
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:400,700|Source+Code+Pro:300,600|Titillium+Web:400,600,700">
        {{ parent() }}
        <style type="text/css" rel="stylesheet">
            #formats {
                font-family: Open Sans,sans-serif;
            }

            .swagger-ui .opblock-tag,
            .swagger-ui .opblock .opblock-section-header label,
            .swagger-ui .opblock .opblock-section-header h4,
            .swagger-ui .opblock .opblock-summary-method,
            .swagger-ui .tab li,
            .swagger-ui .scheme-container .schemes>label,
            .swagger-ui .loading-container .loading:after,
            .swagger-ui .btn,
            .swagger-ui .btn.cancel,
            .swagger-ui select,
            .swagger-ui label,
            .swagger-ui .dialog-ux .modal-ux-content h4,
            .swagger-ui .dialog-ux .modal-ux-header h3,
            .swagger-ui section.models h4,
            .swagger-ui section.models h5,
            .swagger-ui .model-title,
            .swagger-ui .parameter__name,
            .swagger-ui .topbar a,
            .swagger-ui .topbar .download-url-wrapper .download-url-button,
            .swagger-ui .info .title small pre,
            .swagger-ui .scopes h2,
            .swagger-ui .errors-wrapper hgroup h4 {
                font-family: Open Sans,sans-serif!important;
            }
        </style>
    {% endblock stylesheets %}

Endpoints grouping
------------------

Q: Areas feature doesn't fit my needs. So how can I group similar endpoints of one or more controllers in a separate section in the documentation?

A: Use ``#[OA\Tag]`` attribute.

.. configuration-block::

    .. code-block:: php-attributes

        /**
         * Class BookmarkController
         */
        #[OA\Tag(name: "Bookmarks")]
        class BookmarkController extends AbstractFOSRestController implements ContextPresetInterface
        {
            // ...
        }

Disable Default Section
-----------------------

Q: I don't want to render the "default" section, how do I do that?

A: Use ``disable_default_routes`` config in your area.

.. code-block:: yaml

    nelmio_api_doc:
        areas:
            default:
                disable_default_routes: true

Overriding a Form or Plain PHP Object Schema Type
-------------------------------------------------

Q: I'd like to define a PHP object or form with a type other any ``object``, how
do I do that?

A: By using the ``#[OA\Schema]`` attribute/annotation with a ``type`` or ``ref``.
Note, however, that a ``type="object"`` will still read all a models properties.

.. configuration-block::

    .. code-block:: php-attributes

        use Nelmio\ApiDocBundle\Attribute\Model;
        use OpenApi\Attributes as OA;

        /**
         * or define a `ref`:
         * #[OA\Schema(ref: "#/components/schemas/SomeRef")
         */
        #[OA\Schema(type: "array", items: new OA\Items(ref: new Model(type: SomeEntity::class)))]
        class SomeCollection implements \IteratorAggregate
        {
            // ...
        }

PropertyInfo component was unable to guess the type
---------------------------------------------------

Q: I have a property that is not recognized. How can I specify the type?

.. tip::

    Enable the `TypeInfo component`_ in your configuration to improve automatic type guessing:

    .. code-block:: yaml

        nelmio_api_doc:
            type_info: true
            # ...

A: If you want to customize the documentation of an object's property, you can use the ``#[OA\Property]`` attribute or annotate the property with ``@var``::

.. configuration-block::

   .. code-block:: php-attributes

       use Nelmio\ApiDocBundle\Attribute\Model;
       use OpenApi\Attributes as OA;

       class User
       {
           /**
            * @var int
            */
           #[OA\Property(description: 'The unique identifier of the user.')]
           public $id;

           #[OA\Property(type: 'string', maxLength: 255)]
           public $username;

           #[OA\Property(ref: new Model(type: User::class))]
           public $friend;

           #[OA\Property(description: 'This is my coworker!')]
           public setCoworker(User $coworker) {
               // ...
           }
       }

.. _`TypeInfo component`: https://symfony.com/doc/current/components/type_info.html