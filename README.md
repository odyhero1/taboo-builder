# Taboo Builder

Build, customize, and print/share decks for the party game Taboo. This project provides a simple browser UI (served by `index.php`) to create cards where each card has one target word and a list of “taboo” (forbidden) words that clue‑givers must avoid saying.

## Quick Start

- Prerequisite: PHP installed (PHP 8+ recommended).
- From the project directory, start a local PHP server:
  - `php -S localhost:8000`
- Open your browser to `http://localhost:8000`.

## Usage

- Create a card
  - Enter the target word (the word the team is trying to guess).
  - Enter a list of taboo words (the words the clue‑giver must not say).
  - Add the card to your deck.
- Build a deck
  - Repeat for as many cards as you like.
  - Optionally group cards by category or difficulty if your UI supports it.
- Manage cards
  - Edit a card to update its target or taboo words.
  - Delete a card you no longer want.
  - Reorder cards if the interface supports drag‑and‑drop or move controls.
- Save/share your deck
  - Use the app’s save/export option (for example, to JSON or a printable view) if available.
  - To play in person, use a printable view or your browser’s print dialog to create physical cards.


## Tips

- Standard Taboo cards typically include 1 target word and 5 taboo words, but you can choose any count that fits your group.
- Keep taboo words tightly related to the target word to make the challenge fun.
- If you plan to print cards, keep text short and legible.

## Project Notes

- Entry point: `index.php` (serves the builder UI).
- This README focuses on day‑to‑day usage. If your build adds features like import/export formats, theming, or keyboard shortcuts, list them here so players know what’s supported.

