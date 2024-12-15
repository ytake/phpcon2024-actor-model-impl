<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: event.proto

namespace Cart\Event;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>protobuf.ItemRemoved</code>
 */
class ItemRemoved extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string productId = 1;</code>
     */
    protected $productId = '';

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $productId
     * }
     */
    public function __construct($data = NULL) {
        \Cart\Metadata\Event::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>string productId = 1;</code>
     * @return string
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * Generated from protobuf field <code>string productId = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setProductId($var)
    {
        GPBUtil::checkString($var, True);
        $this->productId = $var;

        return $this;
    }

}
