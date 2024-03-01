<?php

declare(strict_types=1);

namespace Spiral\Goridge\RPC;

use Spiral\Goridge\Frame;
use Spiral\Goridge\Relay;
use Spiral\Goridge\RelayInterface;
use Spiral\Goridge\RPC\Codec\JsonCodec;
use Spiral\Goridge\RPC\Exception\RPCException;
use Spiral\Goridge\RPC\Exception\ServiceException;

class RPC implements RPCInterface
{
    /**
     * RPC calls service prefix.
     *
     * @var non-empty-string|null
     */
    private ?string $service = null;

    /**
     * @var positive-int
     */
    private static int $seq = 1;

    public function __construct(
        private readonly RelayInterface $relay,
        private CodecInterface $codec = new JsonCodec(),
    ) {
    }

    /**
     * @psalm-pure
     */
    public function withServicePrefix(string $service): RPCInterface
    {
        /** @psalm-suppress ImpureVariable */
        $rpc = clone $this;
        $rpc->service = $service;

        return $rpc;
    }

    /**
     * @psalm-pure
     */
    public function withCodec(CodecInterface $codec): RPCInterface
    {
        /** @psalm-suppress ImpureVariable */
        $rpc = clone $this;
        $rpc->codec = $codec;

        return $rpc;
    }

    public function call(string $method, mixed $payload, mixed $options = null): mixed
    {
        $this->relay->send($this->packFrame($method, $payload));

        // wait for the frame confirmation
        $frame = $this->relay->waitFrame();

        if (\count($frame->options) !== 2) {
            throw new RPCException('Invalid RPC frame, options missing');
        }

        if ($frame->options[0] !== self::$seq) {
            throw new RPCException('Invalid RPC frame, sequence mismatch');
        }

        self::$seq++;

        return $this->decodeResponse($frame, $options);
    }

    /**
     * @param non-empty-string $connection
     */
    public static function create(string $connection, CodecInterface $codec = new JsonCodec()): RPCInterface
    {
        $relay = Relay::create($connection);

        return new self($relay, $codec);
    }

    /**
     * @throws Exception\ServiceException
     */
    private function decodeResponse(Frame $frame, mixed $options = null): mixed
    {
        // exclude method name
        $body = \substr((string)$frame->payload, $frame->options[1]);

        if ($frame->hasFlag(Frame::ERROR)) {
            $name = $this->relay instanceof \Stringable
                ? (string)$this->relay
                : $this->relay::class;

            throw new ServiceException(\sprintf("Error '%s' on %s", $body, $name));
        }

        return $this->codec->decode($body, $options);
    }

    /**
     * @param non-empty-string $method
     */
    private function packFrame(string $method, mixed $payload): Frame
    {
        if ($this->service !== null) {
            $method = $this->service . '.' . \ucfirst($method);
        }

        $body = $method . $this->codec->encode($payload);
        return new Frame($body, [self::$seq, \strlen($method)], $this->codec->getIndex());
    }
}
