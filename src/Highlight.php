<?php

declare(strict_types=1);

namespace Support;

use Tempest\Highlight\{Highlighter, Language, Languages\Css\CssLanguage, Languages\Php\PhpLanguage};
use Stringable;
use Tempest\Highlight\Languages\Html\HtmlLanguage;
use Tempest\Highlight\Languages\Text\TextLanguage;

final class Highlight implements Stringable
{
    public readonly Highlighter $highlighter;

    public readonly Language $language;

    public function __construct(
        protected string     $code,
        null|string|Language $language = AUTO,
        protected ?int       $gutter = null,
    ) {
        $this->highlighter = new Highlighter();

        if ( AUTO === $language ) {
            $language = $this->autoDetectLanguage();
        }

        $language ??= new TextLanguage();

        if ( $gutter ) {
            $this->highlighter->withGutter( $gutter );
            // return $highlighter->withGutter( $gutter )->parse( $code, $language );
        }
    }

    public function __toString() : string
    {
        return $this->highlighter->parse( $this->code, $this->language );
    }

    public static function code(
        null|string|Stringable $code,
        null|string|Language   $language = AUTO,
        ?int                   $gutter = null,
    ) : string {
        // Bail early on empty strings
        if ( ! $string = (string) $code ) {
            return '';
        }

        return (string) ( new Highlight( $string, $language, $gutter ) );
    }

    protected function autoDetectLanguage() : ?Language
    {
        return match ( true ) {
            $this->isHtml() => new HtmlLanguage(),
            $this->isCss()  => new CssLanguage(),
            $this->isPhp()  => new PhpLanguage(),
            default         => null,
        };
    }

    private function isHtml() : bool
    {
        return (bool) \preg_match( '#^\h*<[a-z-:]*.+>\s*$#m', $this->code );
    }

    private function isCss() : bool
    {
        if ( (bool) \preg_match( '/^[.:#]/m', $this->code ) ) {
            return true;
        }

        return (bool) \preg_match( '/^\h*[a-zA-Z].+:.+;/m', $this->code );
    }

    private function isPhp() : bool
    {
        return \str_starts_with( $this->code, '<?php' );
    }
}
