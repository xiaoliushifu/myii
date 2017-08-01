/**
 * Yii form widget.
 *
 * This is the JavaScript widget used by the yii\widgets\ActiveForm widget.
 *
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
(function ($) {

	//为JQuery类扩展静态方法yiiActiveForm
    $.fn.yiiActiveForm = function (method) {
		//methods是个对象，稍后会看到定义。其中每个对象的属性都是yiiActiveForm允许的方法名
        if (methods[method]) {
			//使用javascript的apply方式调用指定的method。apply里参数传递的方式是数组，call是一个个来传递
			//Array.prototype.slice.call(arguments,1)就好比是arguments.slice(1)
			//不能直接用arguments对象调用Array对象的slice方法。因为arguments称为伪数组，并不是真正的数组。故借用
			//js的apply机制调用，非常棒！
			//methods是一个对象，下面methods[method].apply没有问题
			//但是methods.method.apply()会报错，为什么呢？因为method参数是一个字符串。js对象访问属性的两种方式:
			//Obj.test或者Obj['test']。看出区别了吗？一个是字符串，一个是字面量。针对本例来说，method是一个字符串，
			//所以只能使用Obj['test']这种方式调用
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));

		//或者是一个js对象，或者是一个非假的js其他对象（0,0.0,"",'',NaN,undefined,NULL,false,八个都是假）
		//页面初始化时，AF生成的每个表单项都是一个个js对象。格式如下：
		/*
		{
			"id":"loginform-password",
			"name":"password",
			"container":".field-loginform-password",
			"input":"#loginform-password",
			"error":".help-block.help-block-error",
			"validate":function (attribute, value, messages, deferred, $form) {
				yii.validation.required(value, messages, {"message":"Password cannot be blank."});
				yii.validation.string(value, messages, {"message":"Username must be a string.","max":"5","tooLong":"Username should contain at most 5 characters.","skipOnEmpty":1});
			}
		}
		一个表单项可能有多个规则的验证，上例的validate属性指定的验证函数里，就有required，string两个rule，
		这两个rule都是针对password这一个表单项的验证
		每个表单项对象里除了上述列出的6个属性外，还有一些默认的属性的配置，都在下面的defaults对象中
		*/
        } else if (typeof method === 'object' || !method) {
			//this在这里指的是form的Jquery对象
			//arguments是两个数组。第一个数组的元素都是表单项对象，上面已经给出了格式。
			//第二个数组是个空数组，暂不知有何用
            return methods.init.apply(this, arguments);
		//最后就报错
        } else {
			//调用Jquery的error方法，是一个在控制台输出内容的方法
            $.error('Method ' + method + ' does not exist on jQuery.yiiActiveForm');
            return false;
        }
    };

	//定义穿插在验证代码里的事件列表，如果你想在验证代码里嵌入自己的代码，直接写下面的事件处理函数就行了
	//在合适的时机下，就会调用
	//因为下述事件是写死在验证代码里的，故暂不支持自定义事件名
    var events = {
        /**
		 * 在validate方法里触发
         * beforeValidate event is triggered before validating the whole form.
         * The signature of the event handler should be:
         *     function (event, messages, deferreds)
         * where
         *  - event: an Event object.
         *  - messages: an associative array with keys being attribute IDs and values being error message arrays
         *    for the corresponding attributes.
         *  - deferreds: an array of Deferred objects. You can use deferreds.add(callback) to add a new deferred validation.
         *
         * If the handler returns a boolean false, it will stop further form validation after this event. And as
         * a result, afterValidate event will not be triggered.
         */
        beforeValidate: 'beforeValidate',
        /**
         * afterValidate event is triggered after validating the whole form.
         * The signature of the event handler should be:
         *     function (event, messages, errorAttributes)
         * where
         *  - event: an Event object.
         *  - messages: an associative array with keys being attribute IDs and values being error message arrays
         *    for the corresponding attributes.
         *  - errorAttributes: an array of attributes that have validation errors. Please refer to attributeDefaults for the structure of this parameter.
         */
        afterValidate: 'afterValidate',
        /**
         * beforeValidateAttribute event is triggered before validating an attribute.
         * The signature of the event handler should be:
         *     function (event, attribute, messages, deferreds)
         * where
         *  - event: an Event object.
         *  - attribute: the attribute to be validated. Please refer to attributeDefaults for the structure of this parameter.
         *  - messages: an array to which you can add validation error messages for the specified attribute.
         *  - deferreds: an array of Deferred objects. You can use deferreds.add(callback) to add a new deferred validation.
         *
         * If the handler returns a boolean false, it will stop further validation of the specified attribute.
         * And as a result, afterValidateAttribute event will not be triggered.
         */
        beforeValidateAttribute: 'beforeValidateAttribute',
        /**
         * afterValidateAttribute event is triggered after validating the whole form and each attribute.
         * The signature of the event handler should be:
         *     function (event, attribute, messages)
         * where
         *  - event: an Event object.
         *  - attribute: the attribute being validated. Please refer to attributeDefaults for the structure of this parameter.
         *  - messages: an array to which you can add additional validation error messages for the specified attribute.
         */
        afterValidateAttribute: 'afterValidateAttribute',
        /**
         * beforeSubmit event is triggered before submitting the form after all validations have passed.
         * The signature of the event handler should be:
         *     function (event)
         * where event is an Event object.
         *
         * If the handler returns a boolean false, it will stop form submission.
         */
        beforeSubmit: 'beforeSubmit',
        /**
         * ajaxBeforeSend event is triggered before sending an AJAX request for AJAX-based validation.
         * The signature of the event handler should be:
         *     function (event, jqXHR, settings)
         * where
         *  - event: an Event object.
         *  - jqXHR: a jqXHR object
         *  - settings: the settings for the AJAX request
         */
        ajaxBeforeSend: 'ajaxBeforeSend',
        /**
         * ajaxComplete event is triggered after completing an AJAX request for AJAX-based validation.
         * The signature of the event handler should be:
         *     function (event, jqXHR, textStatus)
         * where
         *  - event: an Event object.
         *  - jqXHR: a jqXHR object
         *  - textStatus: the status of the request ("success", "notmodified", "error", "timeout", "abort", or "parsererror").
         */
        ajaxComplete: 'ajaxComplete'
    };

    // NOTE: If you change any of these defaults, make sure you update yii\widgets\ActiveForm::getClientOptions() as well
	//一些默认配置项
	//注意，这些默认配置项和服务端ActiveForm是一致的，所以修改时要两个都修改，保持一致
	//服务端是yii\widgets\ActiveForm。该类也有下面的一些属性，且有一样的默认配置
    var defaults = {
        // whether to encode the error summary
        encodeErrorSummary: true,
        // the jQuery selector for the error summary
        errorSummary: '.error-summary',
        // whether to perform validation before submitting the form.
        validateOnSubmit: true,
        // the container CSS class representing the corresponding attribute has validation error
		//配置errorCssClass，当验证出错时，为当前表单项的容器添加has-error的css类（红色)
        errorCssClass: 'has-error',
        // the container CSS class representing the corresponding attribute passes validation
		//验证通过时，添加has-success的css类
        successCssClass: 'has-success',
        // the container CSS class representing the corresponding attribute is being validated
		//验证只能一个个去验证，用Css类validating表示当前正在验证的表单项
        validatingCssClass: 'validating',
        // the GET parameter name indicating an AJAX-based validation
		//当开启前端异步ajax验证时，为区别是ajax验证而非真正post数据到服务端，特增加一个GET参数
		//这个参数默认是ajax,也就是说ajax=xxxx存在时，表示这是客户端异步ajax验证
        ajaxParam: 'ajax',
        // the type of data that you're expecting back from the server
		//期待ajax调用后服务端返回的数据类型（需要有jquery的ajax知识点）
        ajaxDataType: 'json',
        // the URL for performing AJAX-based validation. If not set, it will use the the form's action
		//配置ajax验证时的http请求地址，默认是form的action属性值
        validationUrl: undefined,
        // whether to scroll to first visible error after validation.
        scrollToError: true
    };

    // NOTE: If you change any of these defaults, make sure you update yii\widgets\ActiveField::getClientOptions() as well
	//下面的一些默认配置和服务端yii\widgets\ActiveField是一致的，最好都不要修改
    var attributeDefaults = {
        // a unique ID identifying an attribute (e.g. "loginform-username") in a form
        id: undefined,
        // attribute name or expression (e.g. "[0]content" for tabular input)
        name: undefined,
        // the jQuery selector of the container of the input field
        container: undefined,
        // the jQuery selector of the input field under the context of the form
        input: undefined,
        // the jQuery selector of the error tag under the context of the container
        error: '.help-block',
        // whether to encode the error
        encodeError: true,
        // whether to perform validation when a change is detected on the input
        validateOnChange: true,
        // whether to perform validation when the input loses focus
        validateOnBlur: true,
        // whether to perform validation when the user is typing.
        validateOnType: false,
        // number of milliseconds that the validation should be delayed when a user is typing in the input field.
        validationDelay: 500,
        // whether to enable AJAX-based validation.
        enableAjaxValidation: false,
        // function (attribute, value, messages, deferred, $form), the client-side validation function.
        validate: undefined,
        // status of the input field, 0: empty, not entered before, 1: validated, 2: pending validation, 3: validating
        status: 0,
        // whether the validation is cancelled by beforeValidateAttribute event handler
        cancelled: false,
        // the value of the input
        value: undefined
    };


    var submitDefer;

    var setSubmitFinalizeDefer = function($form) {
        submitDefer = $.Deferred();
        $form.data('yiiSubmitFinalizePromise', submitDefer.promise());
    };

    // finalize yii.js $form.submit
    var submitFinalize = function($form) {
        if(submitDefer) {
            submitDefer.resolve();
            submitDefer = undefined;
            $form.removeData('yiiSubmitFinalizePromise');
        }
    };


    var methods = {
		/*
		init方法在视图页面初始化时调用执行，视图页面的底部Jquery加载函数中$('#form-id').yiiActiveForm([{}],[])代码
		attributes是表单项对象数组，options是空数组
		*/
        init: function (attributes, options) {
			//这里的this是form的Jquery对象
            return this.each(function () {
				//这里的this是正在循环的DOM对象。因为外层的each方法的对象this是单一个Jquery对象，非数组，
				//所以凑巧each里的this和each外的this都是一样的。

                var $form = $(this);
				//有数据就不再保存，直接退出
                if ($form.data('yiiActiveForm')) {
                    return;
                }
				//组装数据，使用了上文的defaults对象。extend方法什么意思，看看Jquery手册吧
                var settings = $.extend({}, defaults, options || {});
                if (settings.validationUrl === undefined) {
                    settings.validationUrl = $form.attr('action');
                }

                $.each(attributes, function (i) {
                    attributes[i] = $.extend({value: getValue($form, this)}, attributeDefaults, this);
                    watchAttribute($form, attributes[i]);
                });
				//临时存储数据，注意存储的是什么？怎么存储的，这对后期的验证理解非常关键
                $form.data('yiiActiveForm', {
                    settings: settings,
                    attributes: attributes,
                    submitting: false,
                    validated: false,
                    target: $form.attr('target')
                });

                /**
                 * Clean up error status when the form is reset.
                 * Note that $form.on('reset', ...) does work because the "reset" event does not bubble on IE.
                 */
                $form.bind('reset.yiiActiveForm', methods.resetForm);

                if (settings.validateOnSubmit) {
                    $form.on('mouseup.yiiActiveForm keyup.yiiActiveForm', ':submit', function () {
                        $form.data('yiiActiveForm').submitObject = $(this);
                    });
                    $form.on('submit.yiiActiveForm', methods.submitForm);
                }
            });
        },

        // add a new attribute to the form dynamically.
        // please refer to attributeDefaults for the structure of attribute
        add: function (attribute) {
            var $form = $(this);
            attribute = $.extend({value: getValue($form, attribute)}, attributeDefaults, attribute);
            $form.data('yiiActiveForm').attributes.push(attribute);
            watchAttribute($form, attribute);
        },

        // remove the attribute with the specified ID from the form
        remove: function (id) {
            var $form = $(this),
                attributes = $form.data('yiiActiveForm').attributes,
                index = -1,
                attribute = undefined;
            $.each(attributes, function (i) {
                if (attributes[i]['id'] == id) {
                    index = i;
                    attribute = attributes[i];
                    return false;
                }
            });
            if (index >= 0) {
                attributes.splice(index, 1);
                unwatchAttribute($form, attribute);
            }
            return attribute;
        },

        // manually trigger the validation of the attribute with the specified ID
        validateAttribute: function (id) {
            var attribute = methods.find.call(this, id);
            if (attribute != undefined) {
                validateAttribute($(this), attribute, true);
            }
        },

        // find an attribute config based on the specified attribute ID
        find: function (id) {
            var attributes = $(this).data('yiiActiveForm').attributes,
                result = undefined;
            $.each(attributes, function (i) {
                if (attributes[i]['id'] == id) {
                    result = attributes[i];
                    return false;
                }
            });
            return result;
        },

        destroy: function () {
            return this.each(function () {
                $(this).unbind('.yiiActiveForm');
                $(this).removeData('yiiActiveForm');
            });
        },

        data: function () {
            return this.data('yiiActiveForm');
        },

        // validate all applicable inputs in the form
		//开始验证，这个方法是真正验证的核心，维度最低，无论各种触发验证的事件（submit,blur,change等）都会
		//由validateattribute的定时器函数执行到这里
        validate: function () {
            var $form = $(this),
                data = $form.data('yiiActiveForm'),
                needAjaxValidation = false,
                messages = {},
                deferreds = deferredArray(),
				//这个变量在发挥什么样的作用呢？在Blur触发验证的情况下，submitting是false。
                submitting = data.submitting;
			//每个验证开始之前都会触发beforeValidate这个事件
            var event = $.Event(events.beforeValidate);
            $form.trigger(event, [messages, deferreds]);
			//应该是说这是在点击submit按钮执行的，并不是孤立地执行
            if (submitting) {
                if (event.result === false) {
                    data.submitting = false;
                    submitFinalize($form);
                    return;
                }
            }

            // client-side validation，遍历表单对象的各个表单项进行验证
            $.each(data.attributes, function () {
                if (!$(this.input).is(":disabled")) {
                    this.cancelled = false;
                    // perform validation only if the form is being submitted or if an attribute is pending validation
                    if (data.submitting || this.status === 2 || this.status === 3) {
						//去出msg,然后再放回，这是要干嘛？
                        var msg = messages[this.id];
                        if (msg === undefined) {
                            msg = [];
                            messages[this.id] = msg;
                        }
						//又一个事件触发，这个维度更低，针对每个属性
                        var event = $.Event(events.beforeValidateAttribute);
                        $form.trigger(event, [this, msg, deferreds]);
                        if (event.result !== false) {
                            if (this.validate) {
                                this.validate(this, getValue($form, this), msg, deferreds, $form);
                            }
							//可见，ajax验证是在这里打开的，也就是说，ajax是后续验证的
                            if (this.enableAjaxValidation) {
                                needAjaxValidation = true;
                            }
                        } else {
                            this.cancelled = true;
                        }
                    }
                }
            });

            // ajax validation
			//需要一些Jquery的Deferred对象了解
            $.when.apply(this, deferreds).always(function() {
                // Remove empty message arrays
                for (var i in messages) {
                    if (0 === messages[i].length) {
                        delete messages[i];
                    }
                }
                if ($.isEmptyObject(messages) && needAjaxValidation) {
                    var $button = data.submitObject,
                        extData = '&' + data.settings.ajaxParam + '=' + $form.attr('id');
                    if ($button && $button.length && $button.attr('name')) {
                        extData += '&' + $button.attr('name') + '=' + $button.attr('value');
                    }
					//组装Ajax请求参数
                    $.ajax({
						//url是哪个？
                        url: data.settings.validationUrl,
                        type: $form.attr('method'),
                        data: $form.serialize() + extData,
                        dataType: data.settings.ajaxDataType,
						//从这里可以看出，如果需要得到ajax返回的结果，那就监听ajaxXXXX事件就行了
                        complete: function (jqXHR, textStatus) {
                            $form.trigger(events.ajaxComplete, [jqXHR, textStatus]);
                        },
                        beforeSend: function (jqXHR, settings) {
                            $form.trigger(events.ajaxBeforeSend, [jqXHR, settings]);
                        },
						//从这里看出来，yii.activeForm.js是和服务端的验证配合使用的
                        success: function (msgs) {
                            if (msgs !== null && typeof msgs === 'object') {
                                $.each(data.attributes, function () {
                                    if (!this.enableAjaxValidation || this.cancelled) {
                                        delete msgs[this.id];
                                    }
                                });
                                updateInputs($form, $.extend(messages, msgs), submitting);
                            } else {
                                updateInputs($form, messages, submitting);
                            }
                        },
                        error: function () {
                            data.submitting = false;
                            submitFinalize($form);
                        }
                    });
                } else if (data.submitting) {
                    // delay callback so that the form can be submitted without problem
                    setTimeout(function () {
                        updateInputs($form, messages, submitting);
                    }, 200);
                } else {
                    updateInputs($form, messages, submitting);
                }
            });
        },

        submitForm: function () {
            var $form = $(this),
                data = $form.data('yiiActiveForm');

            if (data.validated) {
				//第二次才会执行这个分支
                // Second submit's call (from validate/updateInputs)
                data.submitting = false;
                var event = $.Event(events.beforeSubmit);
                $form.trigger(event);
                if (event.result === false) {
                    data.validated = false;
                    submitFinalize($form);
                    return false;
                }
                updateHiddenButton($form);
                return true;   // continue submitting the form since validation passes
            } else {
                // First submit's call (from yii.js/handleAction) - execute validating
				//第一次会执行这个分支
                setSubmitFinalizeDefer($form);

                if (data.settings.timer !== undefined) {
                    clearTimeout(data.settings.timer);
                }
                data.submitting = true;
				//开始执行验证
                methods.validate.call($form);
                return false;
            }
        },

        resetForm: function () {
            var $form = $(this);
            var data = $form.data('yiiActiveForm');
            // Because we bind directly to a form reset event instead of a reset button (that may not exist),
            // when this function is executed form input values have not been reset yet.
            // Therefore we do the actual reset work through setTimeout.
            setTimeout(function () {
                $.each(data.attributes, function () {
                    // Without setTimeout() we would get the input values that are not reset yet.
                    this.value = getValue($form, this);
                    this.status = 0;
                    var $container = $form.find(this.container);
                    $container.removeClass(
                        data.settings.validatingCssClass + ' ' +
                            data.settings.errorCssClass + ' ' +
                            data.settings.successCssClass
                    );
                    $container.find(this.error).html('');
                });
                $form.find(data.settings.errorSummary).hide().find('ul').html('');
            }, 1);
        },

        /**
         * Updates error messages, input containers, and optionally summary as well.
         * If an attribute is missing from messages, it is considered valid.
         * @param messages array the validation error messages, indexed by attribute IDs
         * @param summary whether to update summary as well.
         */
        updateMessages: function (messages, summary) {
            var $form = $(this);
            var data = $form.data('yiiActiveForm');
            $.each(data.attributes, function () {
                updateInput($form, this, messages);
            });
            if (summary) {
                updateSummary($form, messages);
            }
        },

        /**
         * Updates error messages and input container of a single attribute.
         * If messages is empty, the attribute is considered valid.
         * @param id attribute ID
         * @param messages array with error messages
         */
        updateAttribute: function(id, messages) {
            var attribute = methods.find.call(this, id);
            if (attribute != undefined) {
                var msg = {};
                msg[id] = messages;
                updateInput($(this), attribute, msg);
            }
        }

    };

    var watchAttribute = function ($form, attribute) {
        var $input = findInput($form, attribute);
		//是否监听change验证
        if (attribute.validateOnChange) {
            $input.on('change.yiiActiveForm', function () {
                validateAttribute($form, attribute, false);
            });
        }
		//是否监听Blur验证
        if (attribute.validateOnBlur) {
            $input.on('blur.yiiActiveForm', function () {
                if (attribute.status == 0 || attribute.status == 1) {
                    validateAttribute($form, attribute, true);
                }
            });
        }
		//是否监听键入验证
        if (attribute.validateOnType) {
            $input.on('keyup.yiiActiveForm', function (e) {
                if ($.inArray(e.which, [16, 17, 18, 37, 38, 39, 40]) !== -1 ) {
                    return;
                }
                if (attribute.value !== getValue($form, attribute)) {
                    validateAttribute($form, attribute, false, attribute.validationDelay);
                }
            });
        }
    };

    var unwatchAttribute = function ($form, attribute) {
        findInput($form, attribute).off('.yiiActiveForm');
    };

	//这个是Yii提供的验证某个属性的方法，无需手动调用，由其他方法调用
	//一般是表单项的change事件，blur事件，还有提交事件都会执行到这里
    var validateAttribute = function ($form, attribute, forceValidate, validationDelay) {
        var data = $form.data('yiiActiveForm');

        if (forceValidate) {
            attribute.status = 2;
        }
		//依次遍历每个attribute。要问每个attribute都有什么，是个对象，对象有哪些属性呢？看attributeDefaults可见一斑
		//其余的
        $.each(data.attributes, function () {
            if (this.value !== getValue($form, this)) {
                this.status = 2;
                forceValidate = true;
            }
        });
        if (!forceValidate) {
            return;
        }
		//清除上次的定时器
        if (data.settings.timer !== undefined) {
            clearTimeout(data.settings.timer);
        }
		//这是validationDelay配置项发挥作用的地方，有些验证并不是马上就调用validate方法
		//而是利用javascript的setTimeout设定一个毫秒级的延迟.
        data.settings.timer = setTimeout(function () {
            if (data.submitting || $form.is(':hidden')) {
                return;
            }
            $.each(data.attributes, function () {
                if (this.status === 2) {
                    this.status = 3;
                    $form.find(this.container).addClass(data.settings.validatingCssClass);
                }
            });
			//执行验证，用$form对象调用methods对象的validate方法，可见validate方法又底层了一些
            methods.validate.call($form);
        }, validationDelay ? validationDelay : 200);
    };

    /**
     * Returns an array prototype with a shortcut method for adding a new deferred.
     * The context of the callback will be the deferred object so it can be resolved like ```this.resolve()```
     * @returns Array
     */
    var deferredArray = function () {
        var array = [];
        array.add = function(callback) {
            this.push(new $.Deferred(callback));
        };
        return array;
    };

    /**
     * Updates the error messages and the input containers for all applicable attributes
     * @param $form the form jQuery object
     * @param messages array the validation error messages
     * @param submitting whether this method is called after validation triggered by form submission
     */
    var updateInputs = function ($form, messages, submitting) {
        var data = $form.data('yiiActiveForm');

        if (submitting) {
            var errorAttributes = [];
            $.each(data.attributes, function () {
                if (!$(this.input).is(":disabled") && !this.cancelled && updateInput($form, this, messages)) {
                    errorAttributes.push(this);
                }
            });

            $form.trigger(events.afterValidate, [messages, errorAttributes]);

            updateSummary($form, messages);

            if (errorAttributes.length) {
                if (data.settings.scrollToError) {
                    var top = $form.find($.map(errorAttributes, function(attribute) {
                        return attribute.input;
                    }).join(',')).first().closest(':visible').offset().top;
                    var wtop = $(window).scrollTop();
                    if (top < wtop || top > wtop + $(window).height()) {
                        $(window).scrollTop(top);
                    }
                }
                data.submitting = false;
            } else {
                data.validated = true;
                var buttonTarget = data.submitObject ? data.submitObject.attr('formtarget') : null;
                if (buttonTarget) {
                    // set target attribute to form tag before submit
                    $form.attr('target', buttonTarget);
                }
                $form.submit();
                // restore original target attribute value
                $form.attr('target', data.target);
            }
        } else {
            $.each(data.attributes, function () {
                if (!this.cancelled && (this.status === 2 || this.status === 3)) {
                    updateInput($form, this, messages);
                }
            });
        }
        submitFinalize($form);
    };

    /**
     * Updates hidden field that represents clicked submit button.
     * @param $form the form jQuery object.
     */
    var updateHiddenButton = function ($form) {
        var data = $form.data('yiiActiveForm');
        var $button = data.submitObject || $form.find(':submit:first');
        // TODO: if the submission is caused by "change" event, it will not work
        if ($button.length && $button.attr('type') == 'submit' && $button.attr('name')) {
            // simulate button input value
            var $hiddenButton = $('input[type="hidden"][name="' + $button.attr('name') + '"]', $form);
            if (!$hiddenButton.length) {
                $('<input>').attr({
                    type: 'hidden',
                    name: $button.attr('name'),
                    value: $button.attr('value')
                }).appendTo($form);
            } else {
                $hiddenButton.attr('value', $button.attr('value'));
            }
        }
    };

    /**
     * Updates the error message and the input container for a particular attribute.
     * @param $form the form jQuery object
     * @param attribute object the configuration for a particular attribute.
     * @param messages array the validation error messages
     * @return boolean whether there is a validation error for the specified attribute
     */
    var updateInput = function ($form, attribute, messages) {
        var data = $form.data('yiiActiveForm'),
            $input = findInput($form, attribute),
            hasError = false;

        if (!$.isArray(messages[attribute.id])) {
            messages[attribute.id] = [];
        }
        $form.trigger(events.afterValidateAttribute, [attribute, messages[attribute.id]]);

        attribute.status = 1;
        if ($input.length) {
            hasError = messages[attribute.id].length > 0;
            var $container = $form.find(attribute.container);
            var $error = $container.find(attribute.error);
            if (hasError) {
                if (attribute.encodeError) {
                    $error.text(messages[attribute.id][0]);
                } else {
                    $error.html(messages[attribute.id][0]);
                }
                $container.removeClass(data.settings.validatingCssClass + ' ' + data.settings.successCssClass)
                    .addClass(data.settings.errorCssClass);
            } else {
                $error.empty();
                $container.removeClass(data.settings.validatingCssClass + ' ' + data.settings.errorCssClass + ' ')
                    .addClass(data.settings.successCssClass);
            }
            attribute.value = getValue($form, attribute);
        }
        return hasError;
    };

    /**
     * Updates the error summary.
     * @param $form the form jQuery object
     * @param messages array the validation error messages
     */
    var updateSummary = function ($form, messages) {
        var data = $form.data('yiiActiveForm'),
            $summary = $form.find(data.settings.errorSummary),
            $ul = $summary.find('ul').empty();

        if ($summary.length && messages) {
            $.each(data.attributes, function () {
                if ($.isArray(messages[this.id]) && messages[this.id].length) {
                    var error = $('<li/>');
                    if (data.settings.encodeErrorSummary) {
                        error.text(messages[this.id][0]);
                    } else {
                        error.html(messages[this.id][0]);
                    }
                    $ul.append(error);
                }
            });
            $summary.toggle($ul.find('li').length > 0);
        }
    };

	/**
	*获得某个表单项的值
	$form Jquery Form 对象
	attribute是DOM对象，某个表单项
	*/
    var getValue = function ($form, attribute) {
        var $input = findInput($form, attribute);
        var type = $input.attr('type');
        if (type === 'checkbox' || type === 'radio') {
            var $realInput = $input.filter(':checked');
            if (!$realInput.length) {
                $realInput = $form.find('input[type=hidden][name="' + $input.attr('name') + '"]');
            }
            return $realInput.val();
        } else {
            return $input.val();
        }
    };

    var findInput = function ($form, attribute) {
        var $input = $form.find(attribute.input);
        if ($input.length && $input[0].tagName.toLowerCase() === 'div') {
            // checkbox list or radio list
            return $input.find('input');
        } else {
            return $input;
        }
    };

})(window.jQuery);


/*
每个attribute的数据格式

*/