<?php

namespace Vonnda\Residential\Model\OneStepCheckout\Request\Payload;

use Magento\Framework\{
    App\RequestInterface,
    DataObject,
    DataObject\Factory as DataObjectFactory,
    Serialize\Serializer\Json as JsonSerializer
};

class Json
{
    /** @constant string FIELD_KEY */
    public const FIELD_KEY = 'customAttributes';

    /** @property DataObjectFactory $dataObjectFactory */
    protected $dataObjectFactory;

    /** @property RequestInterface $request */
    protected $request;

    /** @property JsonSerializer $serializer */
    protected $serializer;

    /**
     * @param DataObjectFactory $dataObjectFactory
     * @param RequestInterface $request
     * @param JsonSerializer $serializer
     * @return void
     */
    public function __construct(
        DataObjectFactory $dataObjectFactory,
        RequestInterface $request,
        JsonSerializer $serializer
    ) {
        $this->dataObjectFactory = $dataObjectFactory;
        $this->request = $request;
        $this->serializer = $serializer;
    }

    /**
     * @param string|null $formKey
     * @return array
     */
    public function getPayload(?string $formKey): array
    {
        /** @var string $content */
        $content = $this->request->getContent() ?? '{}';

        /** @var array $data */
        $data = $this->serializer->unserialize($content);

        if (is_array($data) && $formKey !== null) {
            $data = $data[$formKey] ?? $data;
        }

        return $data;
    }

    /**
     * @param string $dataScope
     * @param string|null $formKey
     * @return array|null
     */
    public function getCustomAttributes(string $dataScope, ?string $formKey): array
    {
        /** @var array $result */
        $result = [];

        /** @var array $payload */
        $payload = $this->getPayload($formKey);

        /** @var array|null $data */
        $data = $payload[$dataScope] ?? null;

        if ($data !== null) {
            /** @var array|null $customAttributes */
            $customAttributes = $data[static::FIELD_KEY] ?? null;

            if ($customAttributes !== null) {
                /** @var array $customAttribute */
                foreach ($customAttributes as $customAttribute) {
                    /** @var string|null $code */
                    $code = $customAttribute['attribute_code'] ?? null;

                    if ($code !== null) {
                        /** @var mixed $value */
                        $value = $customAttribute['value'] ?? null;

                        $result[] = $this->dataObjectFactory->create([
                            $code => $value,
                        ]);
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param string $dataScope
     * @param string $attribute
     * @param string|null $formKey
     * @return DataObject|null
     */
    public function getCustomAttribute(
        string $dataScope,
        string $attribute,
        ?string $formKey
    ): ?DataObject
    {
        /** @var array $customAttributes */
        $customAttributes = $this->getCustomAttributes(
            $dataScope,
            $formKey
        );

        /** @var DataObject $customAttribute */
        foreach ($customAttributes as $customAttribute) {
            if ($customAttribute->hasData($attribute)) {
                return $customAttribute;
            }
        }

        return null;
    }
}
