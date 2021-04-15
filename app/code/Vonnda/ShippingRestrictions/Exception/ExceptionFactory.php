<?php
/**
 * ExceptionFactory.php
 *
 * Factory for generating various exception types.
 * Requires ObjectManager for instance generation.
 *
 * @link      https://devdocs.magento.com/guides/v2.3/extension-dev-guide/factories.html
 * @package   Vonnda_ShippingRestrictions
 */
declare(strict_types=1);

namespace Vonnda\ShippingRestrictions\Exception;

use Magento\Framework\{
    ObjectManagerInterface,
    Phrase,
    PhraseFactory
};

final class ExceptionFactory
{
    /** @constant string BASE_TYPE */
    const BASE_TYPE = \Exception::class;

    /** @constant string ERROR_DEFAULT */
    const ERROR_DEFAULT = 'An error has occurred.';

    /** @property ObjectManagerInterface $objectManager */
    protected $objectManager;

    /** @property PhraseFactory $phraseFactory */
    protected $phraseFactory;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param PhraseFactory $phraseFactory
     * @return void
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        PhraseFactory $phraseFactory
    )
    {
        $this->objectManager = $objectManager;
        $this->phraseFactory = $phraseFactory;
    }

    /**
     * Create exception from type given.
     *
     * @param string|null $type
     * @param Phrase|null $message
     * @return mixed
     * @throws Exception
     */
    public function create(
        ?string $type = self::BASE_TYPE,
        ?Phrase $message = null
    ) {
        /** @var array $arguments */
        $arguments = [];

        if ($type !== self::BASE_TYPE && !is_subclass_of($type, self::BASE_TYPE)) {
            throw new \Exception(
                __(
                    'Invalid exception class type %1 was given.',
                    $type
                )->__toString()
            );
        }

        /* If no message was given, set default message. */
        $message = $message ?? $this->phraseFactory->create(
            [
                'text' => self::ERROR_DEFAULT,
            ]
        );

        if (!is_subclass_of($type, self::BASE_TYPE)) {
            $arguments['message'] = $message->__toString();
        } else {
            $arguments['phrase'] = $message;
        }

        return $this->objectManager->create($type, $arguments);
    }
}
