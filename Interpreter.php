<?php

enum TOKEN: string
{
    case PUSH = 'PUSH';
    case POP = 'POP';
    case SUM = 'SUM';
    case SUB = 'SUB';
    case EXIT = 'EXIT';
    case PRINT = 'PRINT';
    case READ = 'READ';
    case STRING = 'STRING';
    case NUMBER = 'NUMBER';
}

enum TOKEN_TYPE
{
    case PRINTABLE;
    case VARIABLE;
    case MEASURABLE;
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
            TOKEN::SUM->value => TOKEN::SUM,
            TOKEN::SUB->value => TOKEN::SUB,
            TOKEN::NUMBER->value=> TOKEN::NUMBER,
            TOKEN::READ->value => TOKEN::READ
        };
    }

    public static function makeVariableToken(string $token): TokenStreams
    {
        return new TokenStreams(TOKEN::STRING, TOKEN_TYPE::VARIABLE, $token);
    }
    public static function makeStringToken(string $token): TokenStreams
    {
        return new TokenStreams(TOKEN::STRING, TOKEN_TYPE::PRINTABLE, $token);
    }

    public static function makeNumericToken(string $token): TokenStreams
    {
        return new TokenStreams(TOKEN::NUMBER, TOKEN_TYPE::MEASURABLE, $token);
    }
    public static function getTokenType(string $token): TOKEN_TYPE
    {
        return self::isVariable($token) ? TOKEN_TYPE::VARIABLE : TOKEN_TYPE::PRINTABLE;
    }

    public static function isVariable(string $value): bool
    {
        return str_starts_with($value, '#');
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
            $this->tokens[] = TokenStreams::makeVariableToken($token);
        }
        else if(is_numeric($token)){
            $this->tokens[] = TokenStreams::makeNumericToken($token);
        }
        else {
            $this->tokens[] = TokenStreams::makeStringToken($token);
        }
    }

    public function isVariable(string $value)
    {
        return str_starts_with($value, '#');
    }
    public static function isValidToken(string $value): bool
    {
        $tokens = [
            TOKEN::PUSH->value,
            TOKEN::POP->value,
            TOKEN::SUM->value,
            TOKEN::SUB->value,
            TOKEN::EXIT->value,
            TOKEN::PRINT->value,
            TOKEN::READ->value,
            TOKEN::SUB->value
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

    public function parse(): array
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

    private function parseLine($line): void
    {
        $line = trim($line);
        $tokens = explode(' ', $line);
        foreach ($tokens as $token) {
            $this->tokens[] = $token;
        }
    }
}

class Interpreter
{

    public int $programCounter = 0;
    public Tokenizer $tokenizer;
    public  SplStack $stack;

    public function __construct(Tokenizer $tokenizer)
    {
        $this->tokenizer = $tokenizer;
        $this->stack = new SplStack();
    }

    public function execute(): void
    {
//        var_export($this->tokenizer->tokens);
//        echo "map===\n";
//        var_export($this->tokenizer->map);

        while (TOKEN::EXIT != $this->tokenizer->tokens[$this->programCounter]->token) {

            $opCode = $this->tokenizer->tokens[$this->programCounter]->token;
            $this->programCounter++;
            if ($opCode == TOKEN::PRINT) {
                $instruction = $this->tokenizer->tokens[$this->programCounter];
                if ($instruction->tokenType == TOKEN_TYPE::PRINTABLE) {
                    echo $instruction->tokenValue . "\n";
                }
                if ($instruction->tokenType == TOKEN_TYPE::VARIABLE) {
                    $val = $this->tokenizer->map[$instruction->tokenValue];
                    echo $val . "\n";
                }
                $this->programCounter++;
            }

            if($opCode == TOKEN::PUSH){
                $this->stack->push($this->tokenizer->tokens[$this->programCounter]->tokenValue);
                $this->programCounter++;
            }

            if($opCode == TOKEN::SUM){
                $token = $this->tokenizer->tokens[$this->programCounter];
                $operandOne = $this->stack->pop();
                $operandTwo = $this->stack->pop();
                $sum = $operandOne + $operandTwo;
                $this->tokenizer->map[$token->tokenValue] = $sum;
                $this->programCounter++;
            }
            if($opCode == TOKEN::SUB){
                $token = $this->tokenizer->tokens[$this->programCounter];
                $operandOne = $this->stack->pop();
                $operandTwo = $this->stack->pop();
                $sum = $operandTwo - $operandOne;
                $this->tokenizer->map[$token->tokenValue] = $sum;
                $this->programCounter++;
            }
            if($opCode == TOKEN::READ){
                $value = (int)trim(fgets(STDIN));
                $this->stack->push($value);
            }
        }
    }
}

//parser logic
if (!$argv[1]) {
    echo "Please pass the file name";
    return;
}

$parser = new Parser($argv[1]);

$parsedTokens = $parser->parse();

$tokenizer = new Tokenizer();

foreach ($parsedTokens as $token) {
    $tokenizer->tokenize($token);
}

$interpreter = new Interpreter($tokenizer);

$interpreter->execute();
