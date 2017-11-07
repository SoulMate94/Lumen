<?php

可用的验证规则#

accepted#
验证字段值是否为 yes、 on、 1、或 true。这在确认「服务条款」是否同意时相当有用。

active_url#
根据 PHP 函数 dns_get_record，判断要验证的字段必须具有有效的 A 或 AAAA 记录。
// dns_get_record — 获取指定主机的DNS记录

after:date#
验证字段是否是在指定日期之后。这个日期将会通过 strtotime 函数来验证
'start_date' => 'required|date|after:tomorrow'

作为替换 strtotime 传递的日期字符串，你可以指定其它的字段来比较日期：
'finish_date' => 'required|date|after:start_date'

after_or_equal:date#
验证字段必需是等于指定日期或在指定日期之后。更多信息请参见 after 规则。

alpha#
验证字段值是否仅包含字母字符。

alpha_dash#
验证字段值是否仅包含字母、数字、破折号（ - ）以及下划线（ _ ）。

alpha_num#
验证字段值是否仅包含字母、数字。

array#
验证字段必须是一个 PHP 数组。

before:date#
验证字段是否是在指定日期之前。这个日期将会通过 strtotime 函数来验证

before_or_equal:date#
验证字段是否是在指定日期之前。这个日期将会使用 PHP strtotime 函数来验证。

between:min,max#
验证字段值的大小是否介于指定的 min 和 max 之间。字符串、数字、数组或是文件大小的计算方式和 size 规则相同。

boolean#
验证字段值是否能够转换为布尔值。可接受的参数为 true、false、1、0、"1" 以及 "0"

confirmed#
验证字段值必须和 foo_confirmation 的字段值一致。例如，如果要验证的字段是 password，就必须和输入数据里的 password_confirmation 的值保持一致。

date#
验证字段值是否为有效日期，会根据 PHP 的 strtotime 函数来做验证。

date_format:format#
验证字段值符合指定的日期格式 (format)。你应该只使用 date 或 date_format 当中的 其中一个 用于验证，而不应该同时使用两者。

different:field#
验证字段值是否和指定的字段 (field) 有所不同。

digits:value#
验证字段值是否为 numeric 且长度为 value。

digits_between:min,max#
验证字段值的长度是否在 min 和 max 之间。

dimensions#
验证的文件必须是图片并且图片比例必须符合规则：
'avatar' => 'dimensions:min_width=100,min_height=200'
可用的规则为： min_width， max_width ， min_height ， max_height ， width ， height ， ratio 。

比例应该使用宽度除以高度的方式出现。能够使用 3/2 这样的形式设置，也可以使用 1.5 这样的浮点方式：
'avatar' => 'dimensions:ratio=3/2'

由于此规则需要多个参数，因此您可以 Rule::dimensions 方法来构造规则
use Illuminate\Validation\Rule;

Validator::make($data, [
    'avatar' => [
        'required',
        Rule::dimensions()->maxWidth(1000)->maxHeight(500)->ratio(3 / 2),
    ],
]);

distinct#
当你在验证数组的时候，你可以指定某个值必须是唯一的：
'foo.*.id' => 'distinct'

email#
验证字段值是否符合 e-mail 格式。

exists:table,column#
验证字段值是否存在指定的数据表中。

Exists 规则的基本使用方法#
'state' => 'exists:states'

指定一个特定的字段名称#
'state' => 'exists:states,abbreviation'


有时，您可能需要指定要用于 exists 查询的特定数据库连接。你可以使用点「.」语法将数据库连接名称添加到数据表前面来实现这个目的：
'email' => 'exists:connection.staff,email'


如果您想自定义由验证规则执行的查询，您可以使用 Rule 类流畅地定义规则。在这个例子中，我们还将使用数组指定验证规则，而不是使用 | 字符来分隔它们：
use Illuminate\Validation\Rule;

Validator::make($data, [
    'email' => [
        'required',
        Rule::exists('staff')->where(function ($query) {
            $query->where('account_id', 1);
        }),
    ],
]);


file#
必须是成功上传的文件。

filled#
验证的字段必须带有内容。

image#
验证字段文件必须为图片格式（ jpeg、png、bmp、gif、或 svg ）。

in:foo,bar,...#
验证字段值是否有在指定的列表里面。因为这个规则通常需要你 implode 一个数组，Rule::in 方法可以用来流利地构造规则：
use Illuminate\Validation\Rule;

Validator::make($data, [
    'zones' => [
        'required',
        Rule::in(['first-zone', 'second-zone']),
    ],
]);

in_array:anotherfield#
验证的字段必须存在于 anotherfield 的值中。

integer#
验证字段值是否是整数。

ip#
验证字段值是否符合 IP address 的格式。

ipv4#
验证字段值是否符合 IPv4 的格式。

ipv6#
验证字段值是否符合 IPv6 的格式。

json#
验证字段是否是一个有效的 JSON 字符串。

max:value#
字段值必须小于或等于 value。字符串、数字、数组或是文件大小的计算方式和 size 规则相同。

mimetypes:text/plain,...#
验证的文件必须是这些 MIME 类型中的一个：
'video' => 'mimetypes:video/avi,video/mpeg,video/quicktime'

要确定上传文件的MIME类型，会读取文件的内容，并且框架将尝试猜测 MIME 类型，这可能与客户端提供的 MIME 类型不同。
mimes:foo,bar,...#
验证字段文件的 MIME 类型是否符合列表中指定的格式

MIME 规则基本用法#
'photo' => 'mimes:jpeg,bmp,png'
即使你可能只需要验证指定扩展名，但此规则实际上会验证文件的 MIME 类型，其通过读取文件的内容以猜测它的 MIME 类型。
完整的 MIME 类型及对应的扩展名列表可以在下方链接找到：https://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types


min:value#
字段值必须大于或等于 value。字符串、数字、数组或是文件大小的计算方式和 size 规则相同。

nullable#
验证的字段可以为 null。这在验证基本数据类型，如字符串和整型这些能包含 null 值的数据类型中特别有用

not_in:foo,bar,...#
验证字段值必须不在给定的值列表中出现。Rule::notIn方法在构建规则的时候也许有用：
use Illuminate\Validation\Rule;

Validator::make($data, [
    'toppings' => [
        'required',
        Rule::notIn(['sprinkles', 'cherries']),
    ],
]);


numeric#
验证字段值是否为数字。

present#
验证的字段必须出现，但数据可以为空。

regex:pattern#
验证字段值是否符合指定的正则表达式。
Note: 当使用 regex 规则时，你必须使用数组，而不是使用管道分隔符，特别是当正则表达式含有管道符号时

required#
验证字段必须存在输入数据，且不为空。字段符合下方任一条件时即为「空」：
	该值为 null.
	该值为空字符串。
	该值为空数组或空的 可数 对象。
	该值为没有路径的上传文件。

required_if:anotherfield,value,...#
如果指定的其它字段（ anotherfield ）等于任何一个 value 时，此字段为必填。

required_unless:anotherfield,value,...#
如果指定的其它字段（ anotherfield ）等于任何一个 value 时，此字段为不必填。


required_with:foo,bar,...#
如果指定的字段中的 任意一个 有值且不为空，则此字段为必填。

required_with_all:foo,bar,...#
如果指定的 所有 字段都有值，则此字段为必填。

required_without:foo,bar,...#
如果缺少 任意一个 指定的字段，则此字段为必填。

required_without_all:foo,bar,...#
如果所有指定的字段 都没有 值，则此字段为必填。

same:field#
验证字段值和指定的 字段（ field ） 值是否相同。

size:value#
验证字段值的大小是否符合指定的 value 值。对于字符串来说，value 为字符数。对于数字来说，value 为某个整数值。对于数组来说， size 对应的是数组的 count 函数值。对文件来说，size 对应的是文件大小（单位 kb ）。

string#
验证字段值的类型是否为字符串。如果你允许字段的值为 null ，那么你应该将 nullable 规则附加到字段中


timezone#
验证字段值是否是有效的时区，会根据 PHP 的 timezone_identifiers_list 函数来判断


unique:table,column,except,idColumn#
在指定的数据表中，验证字段必须是唯一的。如果没有指定 column，将会使用字段本身的名称。
指定一个特定的字段名称：
'email' => 'unique:users,email_address'

自定义数据库连接#
有时，您可能需要为验证程序所做的数据库查询设置自定义连接。如上面所示，如上所示，将 unique：users 设置为验证规则将使用默认数据库连接来查询数据库。如果要修改数据库连接，请使用「点」语法指定连接和表名：
'email' => 'unique:connection.users,email_address'

强迫 Unique 规则忽略指定 ID：
有时候，你希望在进行字段唯一性验证时对指定 ID 进行忽略。例如，在「更新个人资料」页面会包含用户名、邮箱和地点。这时你会想要验证更新的 E-mail 值是否为唯一的。如果用户仅更改了用户名字段而没有改 E-mail 字段，就不需要抛出验证错误，因为此用户已经是这个 E-mail 的拥有者了。

为了指示验证器忽略用户的ID，我们将使用 Rule 类流畅地定义规则。 在这个例子中，我们还将通过数组来指定验证规则，而不是使用 | 字符来分隔：
use Illuminate\Validation\Rule;

Validator::make($data, [
    'email' => [
        'required',
        Rule::unique('users')->ignore($user->id),
    ],
]);

如果你的数据表使用的主键名称不是 id，则可以在调用 ignore 方法时指定列的名称：
'email' => Rule::unique('users')->ignore($user->id, 'user_id')

增加额外的 Where 语句：
你也可以通过 where 方法指定额外的查询约束条件。例如，我们添加 account_id 为 1 约束条件：
'email' => Rule::unique('users')->where(function ($query) {
    $query->where('account_id', 1);
})


url#
验证字段必需是有效的 URL 格式。


==========================================================
按条件增加规则#

当字段存在的时候进行验证#
在某些情况下，你可能 只想 在输入数据中有此字段时才进行验证。可通过增加 sometimes 规则到规则列表来实现：
$v = Validator::make($data, [
    'email' => 'sometimes|required|email',
]);
// 在上面的例子中，email 字段的验证只会在 $data 数组有此字段时才会进行。


复杂的条件验证#
例如，你可以希望某个指定字段在另一个字段的值超过 100 时才为必填。或者当某个指定字段有值时，另外两个字段要拥有符合的特定值。增加这样的验证条件并不难。首先，利用你熟悉的 static rules 来创建一个 Validator 实例：
$v = Validator::make($data, [
    'email' => 'required|email',
    'games' => 'required|numeric',
]);


为了在特定条件下加入此验证需求，可以在 Validator 实例中使用 sometimes 方法。
$v->sometimes('reason', 'required|max:500', function ($input) {
    return $input->games >= 100;
});
传入 sometimes 方法的第一个参数是我们要用条件认证的字段名称。第二个参数是我们想使用的验证规则。闭包 作为第三个参数传入，如果其返回 true，则额外的规则就会被加入

这个方法可以轻松的创建复杂的条件式验证。你甚至可以一次对多个字段增加条件式验证：
$v->sometimes(['reason', 'cost'], 'required', function ($input) {
    return $input->games >= 100;
});
// 传入 闭包 的 $input 参数是 Illuminate\Support\Fluent 实例，可用来访问你的输入或文件对象


======================================================
验证数组#

验证基于数组的表单输入字段并不一定是一件痛苦的事情。你可以使用「.」来验证一个数组中的属性。例如，如果 HTTP 请求中包含一个 photos[profile] 字段，你可以使用下面的方法
$validator = Validator::make($request->all(), [
    'photos.profile' => 'required|image',
]);


你也可以验证数组中的每一个元素。要验证指定数组输入字段中的每一个 email 是否唯一，可以这么做：
$validator = Validator::make($request->all(), [
    'person.*.email' => 'email|unique:users',
    'person.*.first_name' => 'required_with:person.*.last_name',
]);


同理，你在语言文件定义验证信息的时候可以使用星号 * 字符，可以更加容易的在基于数组格式的字段中使用相同的验证信息：
'custom' => [
    'person.*.email' => [
        'unique' => 'Each person must have a unique e-mail address',
    ]
],


=========================================================
使用规则对象#
Laravel 提供了许多有用的验证规则。但你可能想自定义一些规则。注册自定义验证规则的方法之一，就是使用规则对象。要生成一个新的规则对象，可以使用 make:rule Artisan 命令
接下来，我们使用这个命令来生成一个验证字符串是否是大写的验证对象。Laravel 会将新的规则对象存放在 app/Rules 目录：
php artisan make:rule Uppercase

一旦规则对象生成了，我们就可以定义它的行为。一个规则对象包含两个方法： passes 和 message
passes方法接收属性值和名称，以及根据属性值是否符合规则而返回 true 或者 false 。 message 方法返回验证不通过时应该使用的错误信息。

<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class Uppercase implements Rule
{
    /**
     * 判断验证规则是否通过。
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return strtoupper($value) === $value;
    }

    /**
     * 获取验证错误信息。
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must be uppercase.';
    }
}

当然，如果你希望从翻译文件中返回验证错误信息，你可以从 message 方法中调用 trans 辅助函数
/**
 * 获取验证错误信息。
 *
 * @return string
 */
public function message()
{
    return trans('validation.uppercase');
}
一旦规则对象被定义好后，你可以通过传递一个规则实例的方式，将其和其他验证规则附加到一个验证器：
use App\Rules\Uppercase;

$request->validate([
    'name' => ['required', new Uppercase],
]);


==================================================
使用扩展#
另外一个注册自定义验证规则的方法，就是使用 Validator Facade 中的 extend 方法。让我们在 服务提供者 中使用这个方法来注册自定义的验证规则：
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * 启动任意应用程序服务。
     *
     * @return void
     */
    public function boot()
    {
        Validator::extend('foo', function ($attribute, $value, $parameters, $validator) {
            return $value == 'foo';
        });
    }

    /**
     * 注册服务容器。
     *
     * @return void
     */
    public function register()
    {
        //
    }
}

自定义的验证闭包接收四个参数：要被验证的属性名称 $attribute，属性的值 $value，传入验证规则的参数数组 $parameters，及 Validator 实例。
除了使用闭包，你也可以传入类和方法到 extend 方法中：
Validator::extend('foo', 'FooValidator@validate');

自定义错误消息#
另外你可能还需要为自定义规则来定义一个错误消息。这可以通过使用自定义内联消息数组或是在验证语言包中加入新的规则来实现。此消息应该被放在数组的第一级，而不是被放在 custom 数组内，这是仅针对特定属性的错误消息:
"foo" => "你的输入是无效的!",

"accepted" => ":attribute 必须被接受。",

// 其余的验证错误消息...

当你在创建自定义验证规则时，你可能需要定义占位符来取代错误消息。你可以像上面所描述的那样通过 Validator Facade 来使用 replacer 方法创建一个自定义验证器。通过 服务提供者 中的 boot 方法可以实现：
/**
 * 启动任意应用程序服务。
 *
 * @return void
 */
public function boot()
{
    Validator::extend(...);

    Validator::replacer('foo', function ($message, $attribute, $rule, $parameters) {
        return str_replace(...);
    });
}

隐式扩展功能#
默认情况下，若有一个类似 required 这样的规则，当此规则被验证的属性不存在或包含空值时，其一般的验证规则（包括自定扩展功能）都将不会被运行。例如，当 integer 规则的值为 null 时 unique 将不会被运行：
$rules = ['name' => 'unique'];

$input = ['name' => null];

Validator::make($input, $rules)->passes(); // true

如果要在属性为空时依然运行此规则，则此规则必须暗示该属性为必填。要创建一个「隐式」扩展功能，可以使用 Validator::extendImplicit() 方法：
Validator::extendImplicit('foo', function ($attribute, $value, $parameters, $validator) {
    return $value == 'foo';
});

