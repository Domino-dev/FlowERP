---
name: code-style
description: How Dominik writes code in FlowERP (Nette + Doctrine) — layering, formatting, naming and patterns for entities, repositories, services, facades, presenters, form factories, Latte, JS and CSS. Load before writing or modifying any code in this project.
---

# FlowERP code style

Write new code so it is indistinguishable from the existing code. When in doubt, open the closest existing sibling (e.g. `Product*` for a new CRUD domain) and mirror it.

## Formatting (all files)

- **Indentation: NetBeans style** — indent step 4, tab width 8, tabs used for every full 8 columns:
  - class members: 4 spaces
  - method body: 1 tab
  - nested block: 1 tab + 4 spaces
  - next level: 2 tabs, and so on.
  Same rule in PHP, JS, Latte. CSS uses 4 spaces.
- LF line endings.
- PHP header has no blank lines between the three lines:
  ```php
  <?php
  declare(strict_types=1);
  namespace App\Services;
  ```
- Braces on the same line, no space before them in control structures and return types:
  `if(empty($product)){`, `foreach($items as $item){`, `public function get():?Product{`.
  Constructor keeps the space: `public function __construct(...) {`.
- `} else {`, `} else if(...){`, `try{ ... } catch (\Type $ex){`.
- Uppercase `CONST NAME = ...;` for class constants.
- Blank line after the opening `{` of a method that does more than a one-liner is common; a blank line before the final `return`.

## Imports

Group `use` statements in blocks separated by an empty line, in this order:
1. vendor (Nette, Nettrine, Doctrine, Ramsey…)
2. `App\Helpers`, `App\Forms`, `App\Services`, facade
3. `App\Database\*Repository`
4. `App\Database\<Entity>` classes

Import even same-namespace classes explicitly (e.g. `use App\Database\Price;` inside `App\Database`).

## Naming

- camelCase properties/variables; acronyms upper-cased: `internalID`, `countryISO`, `totalWithVAT`, `VATRateValue`, `productInternalIDs`.
- Long descriptive names over short ones: `$invoiceCustomerBillingAddressData`, `$entityManagerInterface`.
- Raw form data param: `$xxxDataRaw` / `$xxxData` (`\stdClass`).
- Counts: `Cnt` / `Count` suffix (`$productsCnt`).
- Boolean getters keep `get` prefix: `getIsEnabled()`, `getIsWithVAT()`.
- DB: snake_case plural table names (`invoices_items`), FK columns `<table>_id` (`price_lists_id`), indexes `IX_<table>__<cols>`, uniques `UQ_<...>`.
- Public identifiers in URLs/JS are the UUID `internalID`, never the numeric `id`. URL param for detail: short form like `pid`.

## Architecture & layer patterns

`Presenter → Facade → (Service, Repository) → Entity`. Facades, Services and Factories are auto-registered via `search:` in `config/services.neon` (`*Facade`, `*Factory`, `*Service`) — no manual registration.

### Entity (`app/Database/<Entity>.php`)
- `#[ORM\Entity(repositoryClass: XRepository::class)]`, `#[ORM\Table(name:"`table`")]`.
- Every entity: `protected int $id` (generated), `protected string $internalID` (`internal_id`, CHAR(36) unique), `lastUpdate` (`\DateTime`, default CURRENT_TIMESTAMP), `created` (`\DateTimeImmutable`).
- Properties `protected`, typed, one ORM attribute block directly above, no blank lines between properties.
- Constructor takes all scalar fields in order, multi-line params indented by 1 tab + 4 spaces, closing `) {` on the last param line; `lastUpdate`/`created` optional with `?? new \DateTime()`.
- Plain getter for everything, then plain setter for everything (`: void`), then `toArray():?array{ return get_object_vars($this); }`, then collection `addX()/removeX()` returning `self`.

### Repository (`app/Database/<Entity>Repository.php`)
- `final class XRepository extends EntityRepository` with `/** @extends EntityRepository<X> */`.
- Constants for limits: `CONST FIND_PAGINATED_LIMIT = …;` plus `getPaginationLimit():int`.
- QueryBuilder with alias = first letter (`'p'`), one chained call per line, return `new Paginator($qb, true)` for lists. Method names `findPaginated`, `findMultipleBySlug`, `findByXAndY`.

### Service (`app/Services/<Entity>Service.php`)
- Gets `ManagerProvider` in constructor, resolves `EntityManagerInterface` + repositories via `getRepository(Entity::class)`.
- `createXStructure(\stdClass $data, ...): X` — builds a new entity with `UUIDGenerator::generateInternalID()`, casting form strings (`(float)`, `(int)`).
- HTML snippets for autocomplete built with `Nette\Utils\Html::el()` + `data-*` attributes.

### Facade (`app/Presentation/<Entity>/<Entity>Facade.php`)
- Same constructor pattern (ManagerProvider → EM → repositories, then services).
- Methods: `getPaginationLimit`, `getPaginatedXs`, `getXsCount`, `get(?int $id, ?string $internalID = null)`, `create(\stdClass $dataRaw): ?string` (returns internalID), `update(Form $form, \stdClass $dataRaw): bool`, `delete(X $x)`.
- Unwrap the form container first: `$productDataRaw = $productDataRaw->product;`.
- Guard clauses with `empty()` and early `return false/null`.
- Writes wrapped in the standard try/catch ladder; rethrow unique violations so the presenter can show a specific message:
  ```php
	try{
	    $this->entityManagerInterface->flush();
	    return true;
	} catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $ex){
	    // LOG
	    throw $ex;
	} catch (\Doctrine\DBAL\Exception $ex) {
	    // LOG
	} catch (\Throwable $ex){
	    // LOG
	}

	return false;
  ```
- `/** @var X $x */` above `findOneBy` results.
- Short lowercase inline comments describing steps: `// invoice customer creation`, `// set`.

### Presenter (`app/Presentation/<Entity>/<Entity>Presenter.php`)
- `extends \App\Presentation\BasePresenter`, constructor takes `BasePresenterFacade` first and calls `parent::__construct($basePresenterFacade);`.
- State in private properties (`$product`, `$searchSlug = ""`, `$pageNumber = 1`), filled in `actionX`, pushed to template in `renderX` (`$this->template->x = ...`).
- Detail: `actionDetail($pid)` loads by internalID, missing → `flashMessage('Cannot find the product!')` + `redirect('Product:default')`.
- Form component `createComponentXDetailForm(): Form` — new `Form`, call static `XFormFactory::createXForm($form, $this->x)`, then submit `'Save'` (create) or `'Edit'` + `'Delete'` button with `onClick[]` (update).
- Success handlers `xDetailFormSuccess(Form $form, $data)` / `xDetailUpdateFormSuccess(...)`: call facade, catch unique violation → flash + `redirect('this')`, empty result → flash + redirect, success flash + redirect.
- AJAX: `handleXxx(...)` signals; validate input → `$this->sendJson(false);`; otherwise `sendJson(...)` data or set state + `$this->redrawControl('snippet')`.
- Flash messages: English, short, end with `!` — `'The product has been created!'`, `'Cannot update the product!'`, `'Something went wrong!'`.

### Form factory (`app/Forms/<Entity>FormFactory.php`)
- `public static function createXForm(Form $form, ?X $x = null)` mutates the passed form.
- Fields inside a container named after the entity: `$form->addContainer('product')`.
- Each field: `->addText('name','Label')->setHtmlId('product-name')`, kebab-case HTML ids prefixed by entity, hidden `internalID`.
- Required: `->setRequired('Fill the city input!')`.
- At the end `if(!empty($x)){ ...->setDefaultValue($x->getY()); }`.

## Latte (`app/Presentation/<Entity>/*.latte`)
- Blocks in order: `{block styles}`, `{block content}` … `{/block content}`, `{block scripts}` with `<script src="{$basePath}/scripts/<entity>.js"></script>`.
- Manual form rendering: `{form xDetailForm}`, `{label product-name/}` `{input product-name}` inside `.row > .col`.
- Lists inside `n:snippet`, links via `n:href="Product:detail, 'pid' => $product->getInternalID()"`, pagination buttons with `data-page-number`.

## JavaScript (`www/scripts/<entity>.js`)
- Whole file wrapped in `document.addEventListener('DOMContentLoaded', () => { ... });`.
- jQuery delegated events: `$(document).on('click','.selector',function(){ ... });`.
- AJAX through Naja calling presenter signals:
  ```js
	naja.makeRequest('POST','?do=getProductsSearch',{searchSlug:searchSlug},{history:false})
	.then((resp) => {

	})
	.catch((err) => {
	   console.log(err);
	});
  ```
- `let` for locals, `const` for helpers; debounce with `setTimeout`/`clearTimeout` (500 ms) for search inputs.

## CSS (`www/styles/<entity>.css`)
- 4-space indent, one property per line, comments in Czech (`/* Sloupce */`), flex layouts with `gap`, px units, slate palette (`#1e293b`, `#475569`, `#cbd5e1`).

## Language
- Code, identifiers, flash/validation messages, and PHP comments: English.
- CSS comments: Czech is fine.

## Do NOT copy these existing slips
- `catch (Exception ...)` / `catch (Throwable ...)` without a leading backslash inside a namespace — always write `\Exception` / `\Throwable`.
- Leftover `bdump()` calls — don't add debug dumps to finished code.
- NetBeans boilerplate docblocks (`Description of X`, `@author stepa`) — don't add new ones.
- Calling a getter where a setter is meant (e.g. `getVatNumber($value)`).
