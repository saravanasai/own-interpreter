<?php


enum TOKEN: string
{
    case PUSH = 'PUSH';
    case POP = 'POP';
    case SUM = 'SUM';
    case SUB = 'SUB';
    case EXIT = 'EXIT';
    case PRINT = 'PRINT';

    case STRING = 'STRING';
}

enum TOKEN_TYPE
{
    case PRINTABLE;
    case VARIABLE;
}


class TokenStreams
{

    public TOKEN $token;
    public TOKEN_TYPE $tokenType;
    public ?string $tokenValue;


    public function __construct(TOKEN $token, TOKEN_TYPE $tokenType, string $tokenValue = null)
    {
        $this->token = $token;
        $this->tokenType = $tokenType;
        $this->tokenValue = $tokenValue;
    }

    public static function makeToken(string $token)
    {

        $enumToken = self::getToken($token);
        $tokenType = self::getTokenType($token);
        return new TokenStreams($enumToken, $tokenType);
    }

    public static function getToken(string $token): TOKEN
    {

        return match ($token) {
            TOKEN::PRINT->value => TOKEN::PRINT,
            TOKEN::EXIT->value => TOKEN::EXIT,
            TOKEN::POP->value => TOKEN::POP,
            TOKEN::PUSH->value => TOKEN::PUSH,
        };
    }

    public static function makeStringToken(string $token)
    {
        return new TokenStreams(TOKEN::STRING, TOKEN_TYPE::PRINTABLE, $token);
    }

    public static function getTokenType(string $token): TOKEN_TYPE
    {
        return self::isVariable($token) ? TOKEN_TYPE::VARIABLE : TOKEN_TYPE::PRINTABLE;
    }

    public static function isVariable(string $value): bool
    {
        return strpos($value, '#') === 0;
    }
}

class Tokenizer
{
    public array $tokens;

    public array $map;
    public int $tokenCounter;

    public function __construct()
    {
        $this->tokens = [];
        $this->tokenCounter = 0;
        $this->map = [];
    }

    public function tokenize(string $token): void
    {
        if ($this->isValidToken($token)) {
            $this->tokens[] = TokenStreams::makeToken($token);
            $this->tokenCounter++;
            return;
        }

        if ($this->isVariable($token)) {
            $this->map[$token] = 0;
        } else {
            $this->tokens[] = TokenStreams::makeStringToken($token);
        }
    }

    public function isVariable(string $value)
    {
        return strpos($value, '#') === 0;
    }
    public static function isValidToken(string $value): bool
    {
        $tokens = [
            TOKEN::PUSH->value,
            TOKEN::POP->value,
            TOKEN::SUM->value,
            TOKEN::SUB->value,
            TOKEN::EXIT->value,
            TOKEN::PRINT->value
        ];

        return in_array($value, $tokens);
    }
}

class Parser
{
    private $file;
    private $tokens;

    public function __construct($file)
    {
        $this->file = $file;
        $this->tokens = [];
    }

    public function parse()
    {
        $handle = fopen($this->file, 'r');
        if (!$handle) {
            throw new Exception("Could not open file");
        }

        while (($line = fgets($handle)) !== false) {
            $this->parseLine($line);
        }

        fclose($handle);
        return $this->tokens;
    }

    private function parseLine($line)
    {
        $line = trim($line);
        $tokens = explode(' ', $line);
        foreach ($tokens as $token) {
            $this->tokens[] = $token;
        }
    }
}

//parser logic 

if (empty($argv[1])) {
    echo "Please pass the file name";
}

$parser = new Parser($argv[1]);

$parsedTokens = $parser->parse();

$tokenizer = new Tokenizer();

foreach ($parsedTokens as $token) {
    $tokenizer->tokenize($token);
}


class Interpreter
{

    public int $programCounter = 0;
    public Tokenizer $tokenizer;

    public function __construct(Tokenizer $tokenizer)
    {
        $this->tokenizer = $tokenizer;
    }

    public function execute()
    {

        while (TOKEN::EXIT != $this->tokenizer->tokens[$this->programCounter]->token) {

            $opCode = $this->tokenizer->tokens[$this->programCounter]->token;
            $this->programCounter++;

            if ($opCode == TOKEN::PRINT) {

                $instrcution = $this->tokenizer->tokens[$this->programCounter];

                if ($instrcution->tokenType == TOKEN_TYPE::PRINTABLE) {
                    echo "\n" . $instrcution->tokenValue . "\n";
                }

                if ($instrcution->tokenType == TOKEN_TYPE::VARIABLE) {
                    $val = $this->tokenizer->map[$instrcution->token];
                    echo "\n" . $val . "\n";
                }

                $this->programCounter++;
            }
        }
    }
}

$interpreter = new Interpreter($tokenizer);

$interpreter->execute();
