<?php
namespace Vonnda\Cognito\Model\Config\Backend;

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ValueFactory;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;

class JSONWebKey extends \Magento\Framework\App\Config\Value
{

    const JSON_WEB_KEY_STRING_PATH = 'customer_cognito/general/json_web_key';
    const REGION_STRING_PATH = 'customer_cognito/general/user_pool_region';
    protected $configValueFactory;

    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        ValueFactory $configValueFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->configValueFactory = $configValueFactory;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * This class is set as a backend model on the user pool id config setting in order to run this afterSave method to pull in some additional data.
     * This data is the JSON Web Keys for the specified User Pool. These keys contain the hashes used to create the signature of the JSON Web Token.
     * This also sets the user pool region to a config setting, since the region is contained within the id.
     * @return \Magento\Framework\App\Config\Value
     * @throws \Exception
     */
    public function afterSave()
    {
        $userPoolId = $this->getData('value');
        list($region) = explode('_', $userPoolId);
        $jwk = base64_encode(file_get_contents("https://cognito-idp.{$region}.amazonaws.com/{$userPoolId}/.well-known/jwks.json"));
        try {
            $this->configValueFactory->create()->load(self::REGION_STRING_PATH, 'path')
                ->setValue($region)
                ->setPath(self::REGION_STRING_PATH)
                ->save();
            $this->configValueFactory->create()->load(self::JSON_WEB_KEY_STRING_PATH, 'path')
                ->setValue($jwk)
                ->setPath(self::JSON_WEB_KEY_STRING_PATH)
                ->save();
        } catch (\Exception $e) {
            throw new \Exception(__("We can't save new option."));
        }
        return parent::afterSave();
    }

}