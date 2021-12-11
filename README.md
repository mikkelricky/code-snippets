# Code snippets

Insert code snippets in documents:

````md
```
Check out the code for a `.nav-tabs` element in Bootstrap:

<!-- text-snippet(src="https://raw.githubusercontent.com/twbs/bootstrap/v5.1.3/js/tests/visual/tab.html" start='<ul class="nav nav-tabs" role="tablist">' end="</ul>") -->
<!-- end-text-snippet -->
```
````

Running `bin/code-snippets` on the input file will insert the actual code
snippet from
<https://raw.githubusercontent.com/twbs/bootstrap/v5.1.3/js/tests/visual/tab.html>:

````md
<!-- text-snippet(src="https://raw.githubusercontent.com/twbs/bootstrap/v5.1.3/js/tests/visual/tab.html" start='<ul class="nav nav-tabs" role="tablist">' end="</ul>") -->
```html
      <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#home" role="tab" aria-selected="true">Home</button>
        </li>
        <li class="nav-item" role="presentation">
          <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#profile" role="tab">Profile</button>
        </li>
        <li class="nav-item" role="presentation">
          <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#fat" role="tab">@fat</button>
        </li>
        <li class="nav-item" role="presentation">
          <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#mdo" role="tab">@mdo</button>
        </li>
      </ul>
```
<!-- end-text-snippet -->
````

Use `strip-leading-spaces` to, well, strip leading spaces:

````md
<!-- text-snippet(src="https://raw.githubusercontent.com/twbs/bootstrap/v5.1.3/js/tests/visual/tab.html" start='<ul class="nav nav-tabs" role="tablist">' end="</ul>" strip-leading-spaces) -->
```html
<ul class="nav nav-tabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#home" role="tab" aria-selected="true">Home</button>
  </li>
  <li class="nav-item" role="presentation">
    <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#profile" role="tab">Profile</button>
  </li>
  <li class="nav-item" role="presentation">
    <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#fat" role="tab">@fat</button>
  </li>
  <li class="nav-item" role="presentation">
    <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#mdo" role="tab">@mdo</button>
  </li>
</ul>
```
<!-- end-text-snippet -->
````

## Development

```sh
composer install
vendor/bin/phpunit
composer test
composer coding-standards-check
composer code-analysis-run
```
