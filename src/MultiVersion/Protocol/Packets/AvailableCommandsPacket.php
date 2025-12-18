<?php
declare(strict_types=1);

namespace MultiVersion\Protocol\Packets;

use MultiVersion\Protocol\Packets\BasePacket;
use MultiVersion\Utils\BinaryStream;

class AvailableCommandsPacket extends BasePacket {
    protected int $packetId = 0x4c;

    public const ARG_FLAG_VALID = 0x100000;
    public const ARG_FLAG_ENUM = 0x200000;
    public const ARG_FLAG_POSTFIX = 0x1000000;
    public const ARG_FLAG_SOFT_ENUM = 0x4000000;

    public const ARG_TYPE_INT = 1;
    public const ARG_TYPE_FLOAT = 3;
    public const ARG_TYPE_VALUE = 4;
    public const ARG_TYPE_WILDCARD_INT = 5;
    public const ARG_TYPE_OPERATOR = 6;
    public const ARG_TYPE_TARGET = 7;
    public const ARG_TYPE_FILEPATH = 17;
    public const ARG_TYPE_STRING = 32;
    public const ARG_TYPE_POSITION = 38;
    public const ARG_TYPE_MESSAGE = 41;
    public const ARG_TYPE_RAWTEXT = 43;
    public const ARG_TYPE_JSON = 47;
    public const ARG_TYPE_COMMAND = 54;

    public array $enumValues = [];
    public array $postfixes = [];
    public array $enums = [];
    public array $commandData = [];
    public array $dynamicEnums = [];
    public array $enumConstraints = [];

    public function encode(): void {
        $stream = $this->getBuffer();
        $this->encodeHeader($stream);

        $this->encodeEnumValues($stream);
        $this->encodePostfixes($stream);
        $this->encodeEnums($stream);
        $this->encodeCommandData($stream);
        $this->encodeDynamicEnums($stream);
        $this->encodeEnumConstraints($stream);

        $this->encoded = true;
    }

    public function decode(): void {
        $stream = $this->getBuffer();
        $this->decodeHeader($stream);

        $this->decodeEnumValues($stream);
        $this->decodePostfixes($stream);
        $this->decodeEnums($stream);
        $this->decodeCommandData($stream);
        $this->decodeDynamicEnums($stream);
        $this->decodeEnumConstraints($stream);

        $this->decoded = true;
    }

    private function encodeEnumValues(BinaryStream $stream): void {
        $stream->putUnsignedVarInt(count($this->enumValues));
        foreach ($this->enumValues as $value) {
            $stream->putString($value);
        }
    }

    private function decodeEnumValues(BinaryStream $stream): void {
        $count = $stream->getUnsignedVarInt();
        for ($i = 0; $i < $count; ++$i) {
            $this->enumValues[] = $stream->getString();
        }
    }

    private function encodePostfixes(BinaryStream $stream): void {
        $stream->putUnsignedVarInt(count($this->postfixes));
        foreach ($this->postfixes as $postfix) {
            $stream->putString($postfix);
        }
    }

    private function decodePostfixes(BinaryStream $stream): void {
        $count = $stream->getUnsignedVarInt();
        for ($i = 0; $i < $count; ++$i) {
            $this->postfixes[] = $stream->getString();
        }
    }

    private function encodeEnums(BinaryStream $stream): void {
        $stream->putUnsignedVarInt(count($this->enums));
        foreach ($this->enums as $enum) {
            $stream->putString($enum['name']);
            $stream->putUnsignedVarInt(count($enum['values']));
            foreach ($enum['values'] as $valueIndex) {
                if (count($this->enumValues) < 256) {
                    $stream->putByte($valueIndex);
                } elseif (count($this->enumValues) < 65536) {
                    $stream->putLShort($valueIndex);
                } else {
                    $stream->putLInt($valueIndex);
                }
            }
        }
    }

    private function decodeEnums(BinaryStream $stream): void {
        $count = $stream->getUnsignedVarInt();
        $enumValuesCount = count($this->enumValues);

        for ($i = 0; $i < $count; ++$i) {
            $enum = [];
            $enum['name'] = $stream->getString();
            $valueCount = $stream->getUnsignedVarInt();
            $enum['values'] = [];

            for ($j = 0; $j < $valueCount; ++$j) {
                if ($enumValuesCount < 256) {
                    $enum['values'][] = $stream->getByte();
                } elseif ($enumValuesCount < 65536) {
                    $enum['values'][] = $stream->getLShort();
                } else {
                    $enum['values'][] = $stream->getLInt();
                }
            }

            $this->enums[] = $enum;
        }
    }

    private function encodeCommandData(BinaryStream $stream): void {
        $stream->putUnsignedVarInt(count($this->commandData));
        foreach ($this->commandData as $commandData) {
            $stream->putString($commandData['name']);
            $stream->putString($commandData['description']);
            $stream->putLShort($commandData['flags']);
            $stream->putByte($commandData['permission']);
            $stream->putLInt($commandData['aliases'] ?? -1);

            $stream->putUnsignedVarInt(count($commandData['overloads']));
            foreach ($commandData['overloads'] as $overload) {
                $stream->putBool($overload['chaining'] ?? false);
                $stream->putUnsignedVarInt(count($overload['parameters']));

                foreach ($overload['parameters'] as $parameter) {
                    $stream->putString($parameter['name']);
                    $stream->putLInt($parameter['type']);
                    $stream->putBool($parameter['optional']);
                    $stream->putByte($parameter['options'] ?? 0);
                }
            }
        }
    }

    private function decodeCommandData(BinaryStream $stream): void {
        $count = $stream->getUnsignedVarInt();
        for ($i = 0; $i < $count; ++$i) {
            $commandData = [];
            $commandData['name'] = $stream->getString();
            $commandData['description'] = $stream->getString();
            $commandData['flags'] = $stream->getLShort();
            $commandData['permission'] = $stream->getByte();
            $commandData['aliases'] = $stream->getLInt();

            $overloadCount = $stream->getUnsignedVarInt();
            $commandData['overloads'] = [];

            for ($j = 0; $j < $overloadCount; ++$j) {
                $overload = [];
                $overload['chaining'] = $stream->getBool();
                $parameterCount = $stream->getUnsignedVarInt();
                $overload['parameters'] = [];

                for ($k = 0; $k < $parameterCount; ++$k) {
                    $parameter = [];
                    $parameter['name'] = $stream->getString();
                    $parameter['type'] = $stream->getLInt();
                    $parameter['optional'] = $stream->getBool();
                    $parameter['options'] = $stream->getByte();
                    $overload['parameters'][] = $parameter;
                }

                $commandData['overloads'][] = $overload;
            }

            $this->commandData[] = $commandData;
        }
    }

    private function encodeDynamicEnums(BinaryStream $stream): void {
        $stream->putUnsignedVarInt(count($this->dynamicEnums));
        foreach ($this->dynamicEnums as $enum) {
            $stream->putString($enum['name']);
            $stream->putUnsignedVarInt(count($enum['values']));
            foreach ($enum['values'] as $value) {
                $stream->putString($value);
            }
        }
    }

    private function decodeDynamicEnums(BinaryStream $stream): void {
        $count = $stream->getUnsignedVarInt();
        for ($i = 0; $i < $count; ++$i) {
            $enum = [];
            $enum['name'] = $stream->getString();
            $valueCount = $stream->getUnsignedVarInt();
            $enum['values'] = [];

            for ($j = 0; $j < $valueCount; ++$j) {
                $enum['values'][] = $stream->getString();
            }

            $this->dynamicEnums[] = $enum;
        }
    }

    private function encodeEnumConstraints(BinaryStream $stream): void {
        $stream->putUnsignedVarInt(count($this->enumConstraints));
        foreach ($this->enumConstraints as $constraint) {
            $stream->putLInt($constraint['enumValueIndex']);
            $stream->putLInt($constraint['enumIndex']);
            $stream->putUnsignedVarInt(count($constraint['constraints']));
            foreach ($constraint['constraints'] as $value) {
                $stream->putByte($value);
            }
        }
    }

    private function decodeEnumConstraints(BinaryStream $stream): void {
        $count = $stream->getUnsignedVarInt();
        for ($i = 0; $i < $count; ++$i) {
            $constraint = [];
            $constraint['enumValueIndex'] = $stream->getLInt();
            $constraint['enumIndex'] = $stream->getLInt();
            $constraintCount = $stream->getUnsignedVarInt();
            $constraint['constraints'] = [];

            for ($j = 0; $j < $constraintCount; ++$j) {
                $constraint['constraints'][] = $stream->getByte();
            }

            $this->enumConstraints[] = $constraint;
        }
    }

    public function handle(object $handler): bool {
        if (method_exists($handler, 'handleAvailableCommands')) {
            return $handler->handleAvailableCommands($this);
        }
        return false;
    }

    public function canBeSentBeforeLogin(): bool {
        return false;
    }

    public function addEnumValue(string $value): int {
        $this->enumValues[] = $value;
        return count($this->enumValues) - 1;
    }

    public function addEnum(string $name, array $valueIndices): void {
        $this->enums[] = [
            'name' => $name,
            'values' => $valueIndices
        ];
    }

    public function addCommand(string $name, string $description, int $flags, int $permission, array $overloads, int $aliases = -1): void {
        $this->commandData[] = [
            'name' => $name,
            'description' => $description,
            'flags' => $flags,
            'permission' => $permission,
            'aliases' => $aliases,
            'overloads' => $overloads
        ];
    }

    public function addDynamicEnum(string $name, array $values): void {
        $this->dynamicEnums[] = [
            'name' => $name,
            'values' => $values
        ];
    }
}