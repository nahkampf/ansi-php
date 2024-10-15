<?php
/**
 * A tool to parse a string containing tokens and turn them into writer commands
 *
 * eg a string like "%c%%r%%f15%%b3%Hello %f0%world!%r%%lf%" would be equivalent to this:
 * $ansi->eraseDisplay()->nostyle()->color(SGR::COLOR_FG_WHITE_BRIGHT)
 * ->color(SGR::COLOR_BG_CYAN)->text("Hello ")->color(SGR::COLOR_FG_BLACK)
 * ->text("world!")->nostyle()->lf();
 *
 * We are using the standard MS-DOS color codes here:
 * 0  = black           1 = blue
 * 2  = green           3 = cyan
 * 4  = red             5 = magenta
 * 6  = yellow          7 = white
 * 8  = grey            9 = light blue
 * 10 = light green     11 = light cyan
 * 12 = light red       13 = light magenta
 * 14 = light yellow    15 = bright white
 *
 * The *correct* usage would of course be to only allow colors 0-7 as background colors
 * LIKE NATURE INTENDED but we're not going to be that opinionated in code.
 */

namespace Bramus\Ansi;

use Bramus\Ansi\ControlSequences\EscapeSequences\Enums\SGR;

class Parser
{
    /**
     * The main ANSI object
     * @var Ansi
     */
    protected $ansi;
    private $fgColorLookup = [
        0 => SGR::COLOR_FG_BLACK,
        1 => SGR::COLOR_FG_BLUE,
        2 => SGR::COLOR_FG_GREEN,
        3 => SGR::COLOR_FG_CYAN,
        4 => SGR::COLOR_FG_RED,
        5 => SGR::COLOR_FG_PURPLE,
        6 => SGR::COLOR_FG_YELLOW,
        7 => SGR::COLOR_FG_WHITE,
        8 => SGR::COLOR_FG_BLACK_BRIGHT,
        9 => SGR::COLOR_FG_BLUE_BRIGHT,
        10 => SGR::COLOR_FG_GREEN_BRIGHT,
        11 => SGR::COLOR_FG_CYAN_BRIGHT,
        12 => SGR::COLOR_FG_RED_BRIGHT,
        13 => SGR::COLOR_FG_PURPLE_BRIGHT,
        14 => SGR::COLOR_FG_YELLOW_BRIGHT,
        15 => SGR::COLOR_FG_WHITE_BRIGHT,
    ];
    private $bgColorLookup = [
        0 => SGR::COLOR_BG_BLACK,
        1 => SGR::COLOR_BG_BLUE,
        2 => SGR::COLOR_BG_GREEN,
        3 => SGR::COLOR_BG_CYAN,
        4 => SGR::COLOR_BG_RED,
        5 => SGR::COLOR_BG_PURPLE,
        6 => SGR::COLOR_BG_YELLOW,
        7 => SGR::COLOR_BG_WHITE,
        8 => SGR::COLOR_BG_BLACK_BRIGHT,
        9 => SGR::COLOR_BG_BLUE_BRIGHT,
        10 => SGR::COLOR_BG_GREEN_BRIGHT,
        11 => SGR::COLOR_BG_CYAN_BRIGHT,
        12 => SGR::COLOR_BG_RED_BRIGHT,
        13 => SGR::COLOR_BG_PURPLE_BRIGHT,
        14 => SGR::COLOR_BG_YELLOW_BRIGHT,
        15 => SGR::COLOR_BG_WHITE_BRIGHT,
    ];
    public function __construct($ansi)
    {
        $this->ansi = $ansi;
    }

    public function parse($string): void
    {
        $parsed = preg_split("/(%*%)/im", $string, -1);
        foreach ($parsed as $chunk) {
            if ($chunk == "") {
                continue;
            }
            if ($chunk == "b") {
                $this->ansi->bold();
                continue;
            }
            if ($chunk == "n") {
                $this->ansi->normal();
                continue;
            }
            if ($chunk == "f") {
                $this->ansi->faint();
                continue;
            }
            if ($chunk == "i") {
                $this->ansi->italic();
                continue;
            }
            if ($chunk == "u") {
                $this->ansi->underline();
                continue;
            }
            if ($chunk == "bl") {
                $this->ansi->blink();
                continue;
            }
            if ($chunk == "n") {
                $this->ansi->negative();
                continue;
            }
            if ($chunk == "s") {
                $this->ansi->strikethrough();
                continue;
            }
            if (preg_match("/^f\d{1,2}/i", $chunk)) {
                $this->ansi->color($this->fgColorLookup[substr($chunk, 1)]);
                continue;
            }
            if (preg_match("/^b\d{1,2}/i", $chunk)) {
                $this->ansi->color($this->bgColorLookup[substr($chunk, 1)]);
                continue;
            }
            if ($chunk == "r") {
                $this->ansi->nostyle();
                continue;
            }
            if ($chunk == "c") {
                $this->ansi->eraseDisplay();
                continue;
            }
            if ($chunk == "eu") {
                $this->ansi->eraseDisplayUp();
                continue;
            }
            if ($chunk == "ed") {
                $this->ansi->eraseDisplayDown();
                continue;
            }
            if ($chunk == "el") {
                $this->ansi->eraseLine();
                continue;
            }
            if ($chunk == "ee") {
                $this->ansi->eraseLineToEOL();
                continue;
            }
            if ($chunk == "es") {
                $this->ansi->eraseLineToSOL();
                continue;
            }
            if (preg_match("/^cb\d{1,3}/i", $chunk)) {
                $this->ansi->cursorBack(substr($chunk, 2));
                continue;
            }
            if (preg_match("/^cf\d{1,3}/i", $chunk)) {
                $this->ansi->cursorForward(substr($chunk, 2));
                continue;
            }
            if (preg_match("/^cd\d{1,3}/i", $chunk)) {
                $this->ansi->cursorDown(substr($chunk, 2));
                continue;
            }
            if (preg_match("/^cu\d{1,3}/i", $chunk)) {
                $this->ansi->cursorUp(substr($chunk, 2));
                continue;
            }
            if (preg_match("/^xy\d{1,3},\d{1,3}/i", $chunk)) {
                preg_match("/\d{1,3},\d{1,3}/", $chunk, $pos);
                $pos = explode(",", $pos[0]);
                $this->ansi->cursorPosition($pos[0], $pos[1]);
                continue;
            }
            if ($chunk == "lf") {
                $this->ansi->lf();
                continue;
            }
            // finally: write
            $this->ansi->text($chunk);
        }
    }
}
