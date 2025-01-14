<?php

declare(strict_types=1);

namespace Support;

use Tempest\Highlight\{Highlighter, Language, Languages\Css\CssLanguage, Languages\Php\PhpLanguage};
use Stringable;
use Tempest\Highlight\Languages\Html\HtmlLanguage;
use Tempest\Highlight\Languages\Text\TextLanguage;
use ValueError;

final class Highlight implements Stringable
{
    public readonly Highlighter $highlighter;

    public readonly Language $language;

    public readonly string $code;

    private readonly string $source;

    public function __construct(
        null|string|Stringable $code,
        null|string|Language   $language = AUTO,
        protected ?int         $gutter = null,
    ) {
        $this->source      = (string) $code;
        $this->highlighter = new Highlighter();

        if ( AUTO === $language ) {
            $language = $this->autoDetectLanguage();
        }

        $language ??= new TextLanguage();

        if ( $gutter ) {
            $this->highlighter->withGutter( $gutter );
            // return $highlighter->withGutter( $gutter )->parse( $code, $language );
        }

        $this->code     = $this->highlighter->parse( $this->source, $language );
        $this->language = $this->highlighter->getCurrentLanguage() ?? throw new ValueError();
    }

    public function __toString() : string
    {
        return $this->code;
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
        return (bool) \preg_match( '#^\h*<[a-z-:]*.+>\s*$#m', $this->source );
    }

    private function isCss() : bool
    {
        if ( \preg_match( '/^[.:#]/m', $this->source ) ) {
            return true;
        }

        if ( \preg_match( '/^\h*[a-zA-Z].+:.+/m', $this->source ) ) {
            return true;
        }

        return (bool) \preg_match( '/^\h*[a-zA-Z].+:.+;/m', $this->source );
    }

    private function isPhp() : bool
    {
        return \str_starts_with( $this->source, '<?php' );
    }
}
