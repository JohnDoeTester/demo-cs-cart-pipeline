{$parent_names = []}

{foreach $category.parents as $parent}
    {$parent_names[] = $parent.category}
{/foreach}

{if $parent_names}
    {$parent_path = " / "|implode:$parent_names}
{/if}
<span class="select2-selection__choice__handler"></span>
<div class="select2__category-name">{$category.category}</div>
<div class="select2__category-parents">{$parent_path}</div>
{if $category.company && !$runtime.simple_ultimate}
    <div class="select2__category-company">{$category.company}</div>
{/if}