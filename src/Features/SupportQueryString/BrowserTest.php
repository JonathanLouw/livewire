<?php

namespace Livewire\Features\SupportQueryString;

use Livewire\Livewire;
use Livewire\Component;
use Livewire\Attributes\Url;

class BrowserTest extends \Tests\BrowserTestCase
{
    /** @test */
    public function it_does_not_add_null_values_to_the_query_string_array()
    {
        Livewire::visit([
            new class extends \Livewire\Component {
                #[Url]
                public array $tableFilters = [
                    'filter_1' => [
                        'value' => null,
                    ],
                    'filter_2' => [
                        'value' => null,
                    ],
                    'filter_3' => [
                        'value' => null,
                    ]
                ];

                public function render() { return <<<'HTML'
                <div>
                    <input wire:model.live="tableFilters.filter_1.value" type="text" dusk="filter_1" />

                    <input wire:model.live="tableFilters.filter_2.value" type="text" dusk="filter_2" />

                    <input wire:model.live="tableFilters.filter_3.value" type="text" dusk="filter_3" />
                </div>
                HTML; }
            },
        ])
        ->assertInputValue('@filter_1', '')
        ->assertInputValue('@filter_2', '')
        ->assertInputValue('@filter_3', '')
        ->assertQueryStringMissing('tableFilters')
        ->type('@filter_1', 'test')
        ->waitForLivewire()
        ->assertScript(
            '(new URLSearchParams(window.location.search)).toString()',
            'tableFilters%5Bfilter_1%5D%5Bvalue%5D=test'
        )
        ->refresh()
        ->assertInputValue('@filter_1', 'test')
        ;
    }

    public function can_encode_url_containing_spaces_and_commas()
    {
        Livewire::visit([
            new class extends Component
            {
                #[BaseUrl]
                public $space = '';

                #[BaseUrl]
                public $comma = '';

                public function render()
                {
                    return <<<'HTML'
                    <div>
                        <input type="text" dusk="space" wire:model.live="space" />
                        <input type="text" dusk="comma" wire:model.live="comma" />
                    </div>
                    HTML;
                }
            },
        ])
            ->waitForLivewire()
            ->type('@space', 'foo bar')
            ->type('@comma', 'foo,bar')
            ->assertScript('return !! window.location.search.match(/space=foo\+bar/)')
            ->assertScript('return !! window.location.search.match(/comma=foo\,bar/)');
    }

    /** @test */
    public function can_encode_url_containing_reserved_characters()
    {
        Livewire::visit([
            new class extends Component
            {
                #[BaseUrl]
                public $exclamation = '';

                #[BaseUrl]
                public $quote = '';

                #[BaseUrl]
                public $parentheses = '';

                #[BaseUrl]
                public $asterisk = '';

                public function render()
                {
                    return <<<'HTML'
                     <div>
                         <input type="text" dusk="exclamation" wire:model.live="exclamation" />
                         <input type="text" dusk="quote" wire:model.live="quote" />
                         <input type="text" dusk="parentheses" wire:model.live="parentheses" />
                         <input type="text" dusk="asterisk" wire:model.live="asterisk" />
                     </div>
                     HTML;
                }
            },
        ])
            ->waitForLivewire()
            ->type('@exclamation', 'foo!')
            ->type('@parentheses', 'foo(bar)')
            ->type('@asterisk', 'foo*')
            ->assertScript('return !! window.location.search.match(/exclamation=foo\!/)')
            ->assertScript('return !! window.location.search.match(/parentheses=foo\(bar\)/)')
            ->assertScript('return !! window.location.search.match(/asterisk=foo\*/)')
        ;
    }

    /** @test */
    public function can_use_a_value_other_than_initial_for_except_behavior()
    {
        Livewire::visit([
            new class extends Component
            {
                #[BaseUrl(except: '')]
                public $search = '';

                public function mount()
                {
                    $this->search = 'foo';
                }

                public function render()
                {
                    return <<<'HTML'
                    <div>
                        <input type="text" dusk="input" wire:model.live="search" />
                    </div>
                    HTML;
                }
            },
        ])
            ->assertQueryStringHas('search', 'foo')
            ->waitForLivewire()->type('@input', 'bar')
            ->assertQueryStringHas('search', 'bar')
            ->waitForLivewire()->type('@input', ' ')
            ->waitForLivewire()->keys('@input', '{backspace}')
            ->assertQueryStringMissing('search')
        ;
    }

    /** @test */
    public function initial_values_loaded_from_querystring_are_not_removed_from_querystring_on_load_if_they_are_different_to_the_default()
    {
        Livewire::withQueryParams(['perPage' => 25])->visit([
            new class extends Component
            {
                #[BaseUrl]
                public $perPage = '15';

                public function render()
                {
                    return <<<'HTML'
                    <div>
                        <input type="text" dusk="input" wire:model.live="perPage" />
                    </div>
                    HTML;
                }
            },
        ])
            ->waitForLivewireToLoad()
            ->assertQueryStringHas('perPage', '25')
            ->assertInputValue('@input', '25')
        ;
    }

    /** @test */
    public function can_use_except_in_query_string_property()
    {
        Livewire::visit([
            new class extends Component
            {
                protected $queryString = [
                    'search' => [
                        'except' => '',
                        'history' => false,
                    ],
                ];

                public $search = '';

                public function mount()
                {
                    $this->search = 'foo';
                }

                public function render()
                {
                    return <<<'HTML'
                    <div>
                        <input type="text" dusk="input" wire:model.live="search" />
                    </div>
                    HTML;
                }
            },
        ])
            ->assertQueryStringHas('search', 'foo')
            ->waitForLivewire()->type('@input', 'bar')
            ->assertQueryStringHas('search', 'bar')
            ->waitForLivewire()->type('@input', ' ')
            ->waitForLivewire()->keys('@input', '{backspace}')
            ->assertQueryStringMissing('search')
        ;
    }

    /** @test */
    public function can_use_url_on_form_object_properties()
    {
        Livewire::visit([
            new class extends Component
            {
                public FormObject $form;

                public function render()
                {
                    return <<<'HTML'
                    <div>
                        <input type="text" dusk="foo.input" wire:model.live="form.foo" />
                        <input type="text" dusk="bob.input" wire:model.live="form.bob" />
                    </div>
                    HTML;
                }
            },
        ])
            ->assertQueryStringMissing('foo')
            ->assertQueryStringMissing('bob')
            ->assertQueryStringMissing('aliased')
            ->waitForLivewire()->type('@foo.input', 'baz')
            ->assertQueryStringHas('foo', 'baz')
            ->assertQueryStringMissing('bob')
            ->assertQueryStringMissing('aliased')
            ->waitForLivewire()->type('@bob.input', 'law')
            ->assertQueryStringHas('foo', 'baz')
            ->assertQueryStringMissing('bob')
            ->assertQueryStringHas('aliased', 'law')
        ;
    }

    /** @test */
    public function can_use_url_on_string_backed_enum_object_properties()
    {
        Livewire::visit([
            new class extends Component
            {
                #[BaseUrl]
                public StringBackedEnumForUrlTesting $foo = StringBackedEnumForUrlTesting::First;

                public function change()
                {
                    $this->foo = StringBackedEnumForUrlTesting::Second;
                }

                public function render()
                {
                    return <<<'HTML'
                    <div>
                        <button wire:click="change" dusk="button">Change</button>
                        <h1 dusk="output">{{ $foo }}</h1>
                    </div>
                    HTML;
                }
            },
        ])
            ->assertQueryStringMissing('foo')
            ->assertSeeIn('@output', 'first')
            ->waitForLivewire()->click('@button')
            ->assertQueryStringHas('foo', 'second')
            ->assertSeeIn('@output', 'second')
            ->refresh()
            ->assertQueryStringHas('foo', 'second')
            ->assertSeeIn('@output', 'second')
        ;
    }

    /** @test */
    public function can_use_url_on_integer_backed_enum_object_properties()
    {
        Livewire::visit([
            new class extends Component
            {
                #[BaseUrl]
                public IntegerBackedEnumForUrlTesting $foo = IntegerBackedEnumForUrlTesting::First;

                public function change()
                {
                    $this->foo = IntegerBackedEnumForUrlTesting::Second;
                }

                public function render()
                {
                    return <<<'HTML'
                    <div>
                        <button wire:click="change" dusk="button">Change</button>
                        <h1 dusk="output">{{ $foo }}</h1>
                    </div>
                    HTML;
                }
            },
        ])
            ->assertQueryStringMissing('foo')
            ->assertSeeIn('@output', '1')
            ->waitForLivewire()->click('@button')
            ->assertQueryStringHas('foo', '2')
            ->assertSeeIn('@output', '2')
            ->refresh()
            ->assertQueryStringHas('foo', '2')
            ->assertSeeIn('@output', '2')
        ;
    }

    /** @test */
    public function it_does_not_break_string_typed_properties()
    {
        Livewire::withQueryParams(['foo' => 'bar'])
            ->visit([
                new class extends Component
                {
                    #[BaseUrl]
                    public string $foo = '';

                    public function render()
                    {
                        return <<<'HTML'
                        <div>
                            <h1 dusk="output">{{ $foo }}</h1>
                        </div>
                        HTML;
                    }
                },
            ])
            ->assertSeeIn('@output', 'bar')
        ;
    }

    /** @test */
    public function can_use_url_on_lazy_component()
    {
        Livewire::visit([
            new class extends Component
            {
                public function render()
                {
                    return <<<'HTML'
                    <div>
                        <livewire:child lazy />
                    </div>
                    HTML;
                }
            },
            'child' => new class extends Component
            {
                #[BaseUrl]
                public $foo = 'bar';

                public function render()
                {
                    return <<<'HTML'
                    <div>
                        <div>lazy loaded</div>
                        <input type="text" dusk="foo.input" wire:model.live="foo" />
                    </div>
                    HTML;
                }
            },
        ])
            ->waitForText('lazy loaded')
            ->assertQueryStringMissing('foo')
            ->waitForLivewire()->type('@foo.input', 'baz')
            ->assertQueryStringHas('foo', 'baz')
        ;
    }

    /** @test */
    public function can_unset_the_array_key_when_using_dot_notation_without_except()
    {
        Livewire::visit([
            new class extends \Livewire\Component {
                public array $tableFilters = [];

                protected function queryString() {
                    return [
                        'tableFilters.filter_1.value' => [
                            'as' => 'filter',
                        ],
                    ];
                }

                public function clear()
                {
                    unset($this->tableFilters['filter_1']['value']);
                }

                public function render() { return <<<'HTML'
                <div>
                    <input wire:model.live="tableFilters.filter_1.value" type="text" dusk="filter" />

                    <span dusk="output">@json($tableFilters)</span>

                    <button dusk="clear" wire:click="clear">Clear</button>
                </div>
                HTML; }
            },
        ])
            ->assertInputValue('@filter', '')
            ->waitForLivewire()->type('@filter', 'foo')
            ->assertSeeIn('@output', '{"filter_1":{"value":"foo"}}')
            ->waitForLivewire()->click('@clear')
            ->assertInputValue('@filter', '')
            ->assertQueryStringMissing('filter')
        ;
    }

    /** @test */
    public function can_unset_the_array_key_when_with_except()
    {
        Livewire::visit([
            new class extends \Livewire\Component {
                public array $tableFilters = [];

                protected function queryString() {
                    return [
                        'tableFilters' => [
                            'filter_1' => [
                                'value' => [
                                    'as' => 'filter',
                                    'except' => '',
                                ],
                            ]
                        ],
                    ];
                }

                public function clear()
                {
                    unset($this->tableFilters['filter_1']['value']);
                }

                public function render() { return <<<'HTML'
                <div>
                    <input wire:model.live="tableFilters.filter_1.value" type="text" dusk="filter" />

                    <span dusk="output">@json($tableFilters)</span>

                    <button dusk="clear" wire:click="clear">Clear</button>
                </div>
                HTML; }
            },
        ])
            ->assertInputValue('@filter', '')
            ->waitForLivewire()->type('@filter', 'foo')
            ->assertSeeIn('@output', '{"filter_1":{"value":"foo"}}')
            ->waitForLivewire()->click('@clear')
            ->assertInputValue('@filter', '')
            ->assertQueryStringMissing('filter')
        ;
    }

    /** @test */
    public function can_unset_the_array_key_when_without_except()
    {
        Livewire::visit([
            new class extends \Livewire\Component {
                public array $tableFilters = [];

                protected function queryString() {
                    return [
                        'tableFilters' => [
                            'filter_1' => [
                                'value' => [
                                    'as' => 'filter',
                                ],
                            ]
                        ],
                    ];
                }

                public function clear()
                {
                    unset($this->tableFilters['filter_1']['value']);
                }

                public function render() { return <<<'HTML'
                <div>
                    <input wire:model.live="tableFilters.filter_1.value" type="text" dusk="filter" />

                    <span dusk="output">@json($tableFilters)</span>

                    <button dusk="clear" wire:click="clear">Clear</button>
                </div>
                HTML; }
            },
        ])
            ->assertInputValue('@filter', '')
            ->waitForLivewire()->type('@filter', 'foo')
            ->assertSeeIn('@output', '{"filter_1":{"value":"foo"}}')
            ->waitForLivewire()->click('@clear')
            ->assertInputValue('@filter', '')
            ->assertQueryStringMissing('filter')
        ;
    }

    /** @test */
    public function can_unset_the_array_key_when_using_dot_notation_with_except()
    {
        Livewire::visit([
            new class extends \Livewire\Component {
                public array $tableFilters = [];

                protected function queryString() {
                    return [
                        'tableFilters.filter_1.value' => [
                            'as' => 'filter',
                            'except' => ''
                        ],
                    ];
                }

                public function clear()
                {
                    unset($this->tableFilters['filter_1']['value']);
                }

                public function render() { return <<<'HTML'
                <div>
                    <input wire:model.live="tableFilters.filter_1.value" type="text" dusk="filter" />

                    <span dusk="output">@json($tableFilters)</span>

                    <button dusk="clear" wire:click="clear">Clear</button>
                </div>
                HTML; }
            },
        ])
            ->assertInputValue('@filter', '')
            ->waitForLivewire()->type('@filter', 'foo')
            ->assertSeeIn('@output', '{"filter_1":{"value":"foo"}}')
            ->waitForLivewire()->click('@clear')
            ->assertInputValue('@filter', '')
            ->assertQueryStringMissing('filter')
        ;
    }

    /** @test */
    public function can_handle_empty_querystring_value_as_empty_string()
    {
        Livewire::visit([
            new class extends Component
            {
                #[Url]
                public $foo;

                public function setFoo()
                {
                    $this->foo = 'bar';
                }

                public function unsetFoo()
                {
                    $this->foo = '';
                }

                public function render()
                {
                    return <<<'HTML'
                    <div>
                        <button wire:click="setFoo" dusk="setButton">Set foo</button>
                        <button wire:click="unsetFoo" dusk="unsetButton">Unset foo</button>
                        <span dusk="output">@js($foo)</span>
                    </div>
                    HTML;
                }
            },
        ])
            ->assertQueryStringMissing('foo')
            ->waitForLivewire()->click('@setButton')
            ->assertSeeIn('@output', '\'bar\'')
            ->assertQueryStringHas('foo', 'bar')
            ->refresh()
            ->assertQueryStringHas('foo', 'bar')
            ->waitForLivewire()->click('@unsetButton')
            ->assertSeeIn('@output', '\'\'')
            ->assertQueryStringHas('foo', '')
            ->refresh()
            ->assertSeeIn('@output', '\'\'')
            ->assertQueryStringHas('foo', '');
    }

    /** @test */
    public function can_handle_empty_querystring_value_as_null()
    {
        Livewire::visit([
            new class extends Component
            {
                #[Url(nullable: true)]
                public $foo;

                public function setFoo()
                {
                    $this->foo = 'bar';
                }

                public function unsetFoo()
                {
                    $this->foo = null;
                }

                public function render()
                {
                    return <<<'HTML'
                    <div>
                        <button wire:click="setFoo" dusk="setButton">Set foo</button>
                        <button wire:click="unsetFoo" dusk="unsetButton">Unset foo</button>
                        <span dusk="output">@js($foo)</span>
                    </div>
                    HTML;
                }
            },
        ])
            ->assertQueryStringMissing('foo')
            ->waitForLivewire()->click('@setButton')
            ->assertSeeIn('@output', '\'bar\'')
            ->assertQueryStringHas('foo', 'bar')
            ->refresh()
            ->assertQueryStringHas('foo', 'bar')
            ->waitForLivewire()->click('@unsetButton')
            ->assertSeeIn('@output', 'null')
            ->assertQueryStringHas('foo', '')
            ->refresh()
            ->assertSeeIn('@output', 'null')
            ->assertQueryStringHas('foo', '');
    }

    /** @test */
    public function can_handle_empty_querystring_value_as_null_or_empty_string_based_on_typehinting_of_property()
    {
        Livewire::visit([
            new class extends Component
            {
                #[Url]
                public ?string $nullableFoo;

                #[Url]
                public string $notNullableFoo;

                #[Url]
                public $notTypehintingFoo;

                public function setFoo()
                {
                    $this->nullableFoo = 'bar';
                    $this->notNullableFoo = 'bar';
                    $this->notTypehintingFoo = 'bar';
                }

                public function unsetFoo()
                {
                    $this->nullableFoo = null;
                    $this->notNullableFoo = '';
                    $this->notTypehintingFoo = null;
                }

                public function render()
                {
                    return <<<'HTML'
                    <div>
                        <button wire:click="setFoo" dusk="setButton">Set foo</button>
                        <button wire:click="unsetFoo" dusk="unsetButton">Unset foo</button>
                        <span dusk="output-nullableFoo">@js($nullableFoo)</span>
                        <span dusk="output-notNullableFoo">@js($notNullableFoo)</span>
                        <span dusk="output-notTypehintingFoo">@js($notTypehintingFoo)</span>
                    </div>
                    HTML;
                }
            },
        ])
            ->assertQueryStringMissing('nullableFoo')
            ->assertQueryStringMissing('notNullableFoo')
            ->assertQueryStringMissing('notTypehintingFoo')
            ->waitForLivewire()->click('@setButton')
            ->assertSeeIn('@output-nullableFoo', '\'bar\'')
            ->assertSeeIn('@output-notNullableFoo', '\'bar\'')
            ->assertSeeIn('@output-notTypehintingFoo', '\'bar\'')
            ->assertQueryStringHas('nullableFoo', 'bar')
            ->assertQueryStringHas('notNullableFoo', 'bar')
            ->assertQueryStringHas('notTypehintingFoo', 'bar')
            ->refresh()
            ->assertQueryStringHas('nullableFoo', 'bar')
            ->assertQueryStringHas('notNullableFoo', 'bar')
            ->assertQueryStringHas('notTypehintingFoo', 'bar')
            ->waitForLivewire()->click('@unsetButton')
            ->assertSeeIn('@output-nullableFoo', 'null')
            ->assertSeeIn('@output-notNullableFoo', '\'\'')
            ->assertSeeIn('@output-notTypehintingFoo', 'null')
            ->assertQueryStringHas('nullableFoo', '')
            ->assertQueryStringHas('notNullableFoo', '')
            ->assertQueryStringHas('notTypehintingFoo', '')
            ->refresh()
            ->assertSeeIn('@output-nullableFoo', 'null')
            ->assertSeeIn('@output-notNullableFoo', '\'\'')
            ->assertSeeIn('@output-notTypehintingFoo', '\'\'')
            ->assertQueryStringHas('nullableFoo', '')
            ->assertQueryStringHas('notNullableFoo', '')
            ->assertQueryStringHas('notTypehintingFoo', '');
    }

    /** @test */
    public function can_set_the_correct_query_string_parameter_when_multiple_instances_of_the_same_component_are_used()
    {
        Livewire::visit([
            new class extends Component {
                public function render()
                {
                    return <<<'HTML'
                    <div>
                        <livewire:child queryParameterName="foo" />
                        <livewire:child queryParameterName="bar" />
                    </div>
                    HTML;
                }
            },
            'child' => new class extends Component {
                public $queryParameterName;
                public $value = '';

                protected function queryString()
                {
                    return [
                        'value' => ['as' => $this->queryParameterName],
                    ];
                }

                public function render()
                {
                    return <<<'HTML'
                    <div>
                        <input wire:model.live="value" type="text" dusk="input" />
                    </div>
                    HTML;
                }
            },
        ])
            ->waitForLivewire()->type('input', 'test')
            ->assertQueryStringHas('foo', 'test') // Type into the first component's input...
            ->assertQueryStringMissing('bar')
        ;
    }
}

class FormObject extends \Livewire\Form
{
    #[\Livewire\Attributes\Url]
    public $foo = 'bar';

    #[\Livewire\Attributes\Url(as: 'aliased')]
    public $bob = 'lob';
}

enum StringBackedEnumForUrlTesting: string
{
    case First = 'first';
    case Second = 'second';
}

enum IntegerBackedEnumForUrlTesting: int
{
    case First = 1;
    case Second = 2;
}
