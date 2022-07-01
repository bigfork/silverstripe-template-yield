# Silverstripe template yield

Adds support for `<% section %>` and `<% yield %>` tags to Silverstripe.

⚠️ this is a proof of concept built by using HTTP middleware to perform string replacements in order to work around
missing extension hooks. See the [limitations](#limitations) section for more info.

## Usage examples

You can "yield" content that’s provided by section tags that exist in any templates in the current render process.

```html
<!-- Page.ss -->
<head>
    <% yield 'MetaTags' %>
</head>

<!-- Includes/Pagination.ss -->
<% section 'MetaTags' %>
    <meta rel="next" href="{$NextLink}" />
    <meta rel="prev" href="{$PrevLink}" />
<% end_section %>

<div class="pagination">
    <ul>
        ...
```

You can also offer fallback content:

```html
<head>
    <!-- If nothing includes a <% section 'MetaTitle' %>, the following fallback will be rendered -->
    <% yield 'MetaTitle' %>
        <title>Some default meta title</title>
    <% end_yield %>
</head>
```

There are also inline (“open”) tags which are useful for yielding things like CSS classes:

```html
<!-- Page.ss -->
<body class="<% yield 'BodyClass' %>">

</body>

<!-- Layout/MyPage.ss -->
<% section 'BodyClass', 'some-css-class' %>

<div class="typography">
    ... etc
</div>
```

As above, you can offer a fallback for inline tags too:

```html
<!-- Will return fallback-class if no <% section 'BodyClass' %> is defined -->
<body class="<% yield 'BodyClass', 'fallback-class' %>">

</body>
```

## Limitations

Due to there not yet being suitable hooks in place in Silverstripe core, this has the following limitations:

- This only works for templates that are returned via an HTTP request, as it relies on HTTP middleware to inject the
yielded content. If you need to use this for other content (e.g. emails or server-side rendering) you will need to call
`BlockProvider::yieldIntoString()` yourself
- While nested sections/yields appear to work, these haven’t been thoroughly tested
