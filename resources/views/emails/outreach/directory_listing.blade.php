@component('mail::message')
@lang('outreach.directory.greeting', ['name' => $contactName])

@lang('outreach.directory.intro', ['directory' => $directoryName])

@lang('outreach.directory.description')

**@lang('outreach.directory.features_title')**
- @lang('outreach.directory.feature_1')
- @lang('outreach.directory.feature_2')
- @lang('outreach.directory.feature_3')
- @lang('outreach.directory.feature_4')
- @lang('outreach.directory.feature_5')
- @lang('outreach.directory.feature_6')

@lang('outreach.directory.ask')

@component('mail::button', ['url' => $websiteUrl])
@lang('outreach.directory.cta')
@endcomponent

@lang('outreach.directory.closing')

<small><a href="{{ $unsubscribeUrl }}" style="color: #999;">@lang('outreach.unsubscribe')</a></small>
@endcomponent
