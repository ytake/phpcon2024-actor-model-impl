<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: event.proto

namespace Calculator\ProtoBuf;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>protobuf.Subtracted</code>
 */
class Subtracted extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>double result = 1;</code>
     */
    protected $result = 0.0;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type float $result
     * }
     */
    public function __construct($data = NULL) {
        \Calculator\Metadata\ProtoBuf\Event::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>double result = 1;</code>
     * @return float
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Generated from protobuf field <code>double result = 1;</code>
     * @param float $var
     * @return $this
     */
    public function setResult($var)
    {
        GPBUtil::checkDouble($var);
        $this->result = $var;

        return $this;
    }

}

