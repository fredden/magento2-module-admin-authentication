<?php

namespace Fredden\AdminAuth\Plugin\Magento;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Locale\OptionInterface;
use Magento\User\Model\User;

class SetAdminUserLocale
{
    public function __construct(
        private readonly OptionInterface $deployedLocales,
        private readonly RequestInterface $request,
    ) {
    }

    /**
     * Ensure that the user always has a valid interface locale set
     */
    public function afterBeforeSave(User $subject, User $result): User
    {
        $availableOptions = $this->deployedLocales->getOptionLocales();

        if (!$availableOptions) {
            return $result;
        }

        $currentUserLocale = $subject->getInterfaceLocale();
        $locales = [];

        foreach ($availableOptions as $option) {
            if ($option['value'] === $currentUserLocale) {
                // User's current locale is available here; there's nothing for us to do.
                return $result;
            }

            $locales[$option['value']] = $option['value'];
            $locales[substr($option['value'], 0, 2)] = $option['value'];
        }

        $preferredLocales = [];

        if ($header = $this->request->getHeader('accept-language')) {
            $languages = explode(',', $header);
            foreach ($languages as $languageOption) {
                // TODO: parse and use ';q=' values to weight/sort options.
                $languageOption = explode(';', $languageOption)[0];
                $languageOption = trim($languageOption);
                $languageOption = str_replace('-', '_', $languageOption);

                $preferredLocales[] = $languageOption;
            }
        }

        $preferredLocales[] = 'en'; // Add English as a fallback option

        foreach ($preferredLocales as $localeOption) {
            if ($localeOption === 'en' && isset($locales['en_GB'])) {
                // English is preferred.
                $localeOption = 'en_GB';
            }

            if ($localeOption === 'en' && isset($locales['en_US'])) {
                // American is next best if English isn't available.
                $localeOption = 'en_US';
            }

            if (isset($locales[$localeOption])) {
                $subject->setInterfaceLocale($locales[$localeOption]);
                return $result;
            }
        }

        // We didn't find a locale that suits the user's preferences. Let's just
        // give them ANY locale that exists. A working admin in a foreign
        // language is better than a completely broken admin. The user can set
        // another locale when they work out where to click.
        $subject->setInterfaceLocale($option['value']);
        // $option['value'] comes from the foreach() on line 44. There will
        // always be at least one locale in that loop due to Magento internals.

        return $result;
    }
}
