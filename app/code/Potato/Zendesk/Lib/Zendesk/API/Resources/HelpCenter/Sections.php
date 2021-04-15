<?php

namespace Potato\Zendesk\Lib\Zendesk\API\Resources\HelpCenter;

use Potato\Zendesk\Lib\Zendesk\API\Traits\Resource\Defaults;
use Potato\Zendesk\Lib\Zendesk\API\Traits\Resource\Locales;

/**
 * Class Sections
 * https://developer.zendesk.com/rest_api/docs/help_center/categories
 */
class Sections extends ResourceAbstract
{
    use Defaults;
    use Locales {
        getRoute as protected localesGetRoute;
    }

    /**
     * {@inheritdoc}
     */
    protected $objectName = 'section';

    /**
     * @inheritdoc
     */
    protected function setUpRoutes()
    {
        $this->setRoutes([
            'updateSourceLocale' =>  "{$this->resourceName}/{sectionId}/source_locale.json",
            'create' =>  "{$this->prefix}categories/{category_id}/sections.json",
        ]);
    }

    /**
     * Returns a route and replaces tokenized parts of the string with
     * the passed params
     *
     * @param string $name
     * @param array $params
     *
     * @return mixed The default routes, or if $name is set to `findAll`, any of the following formats
     * based on the parent chain
     * GET /api/v2/helpcenter/sections.json
     * GET /api/v2/helpcenter/categories/{category_id}/sections.json
     *
     * @throws \Exception
     */
    public function getRoute($name, array $params = [])
    {
        $lastChained = $this->getLatestChainedParameter();
        $params = $this->addChainedParametersToParams($params, [
            'category_id' => Categories::class
        ]);

        if (empty($lastChained) || $name !== 'findAll') {
            return $this->localesGetRoute($name, $params);
        } else {
            $chainedResourceName = array_keys($lastChained)[0];
            $id = $lastChained[$chainedResourceName];
            if ($chainedResourceName === Categories::class) {
                $locales = $this->getLocale();
                if ($locales) {
                    return "{$this->prefix}{$locales}/categories/$id/sections.json";
                } else {
                    return "{$this->prefix}categories/$id/sections.json";
                }
            } else {
                return $this->localesGetRoute($name, $params);
            }
        }
    }
}
