import { ref, computed, type Ref } from 'vue';

// Character mappings from JTK library
const TRANS_FROM = "qwertyuiop[]\\asdfghjkl;'zxcvbnm,./QWERTYUIOP{}|ASDFGHJKL:\"ZXCVBNM<>?()";

const LAYOUTS = {
    phonetic: "ްއެރތޔުިޮޕ][\\ަސދފގހޖކލ؛'ޒ×ޗވބނމ،./ޤޢޭޜޓޠޫީޯ÷}{|ާށޑﷲޣޙޛޚޅ:\"ޡޘޝޥޞޏޟ><؟)(",
    'phonetic-hh': "ޤަެރތޔުިޮޕ][\\އސދފގހޖކލ؛'ޒޝްވބނމ،./ﷲާޭޜޓޠޫީޯޕ}{|ޢށޑޟޣޙޛޚޅ:\"ޡޘޗޥޞޏމ><؟)(",
    typewriter: "ޫޮާީޭގރމތހލ[]ިުްަެވއނކފﷲޒޑސޔޅދބށޓޯ×'\"/:ޤޜޣޠޙ÷{}<>.،\"ޥޢޘޚޡ؛ޖޕޏޗޟޛޝ\\ޞ؟)(",
} as const;

export type DhivehiLayout = keyof typeof LAYOUTS;

export interface UseDhivehiKeyboardOptions {
    defaultLayout?: DhivehiLayout;
    defaultEnabled?: boolean;
}

export interface UseDhivehiKeyboardReturn {
    enabled: Ref<boolean>;
    layout: Ref<DhivehiLayout>;
    toggle: () => void;
    enable: () => void;
    disable: () => void;
    setLayout: (newLayout: DhivehiLayout) => void;
    translate: (char: string) => string;
    handleKeyDown: (e: KeyboardEvent, el: HTMLInputElement | HTMLTextAreaElement) => void;
    direction: Ref<'ltr' | 'rtl'>;
    layoutOptions: { label: string; value: DhivehiLayout }[];
}

/**
 * Vue composable for Dhivehi/Thaana keyboard input
 * Ported from JTK (Jawish Thaana Keyboard) library
 * @see https://github.com/jawish/jtk
 */
export function useDhivehiKeyboard(options: UseDhivehiKeyboardOptions = {}): UseDhivehiKeyboardReturn {
    const { defaultLayout = 'phonetic', defaultEnabled = false } = options;

    const enabled = ref(defaultEnabled);
    const layout = ref<DhivehiLayout>(defaultLayout);

    const currentMap = computed(() => LAYOUTS[layout.value]);
    const direction = computed<'ltr' | 'rtl'>(() => (enabled.value ? 'rtl' : 'ltr'));

    const layoutOptions: { label: string; value: DhivehiLayout }[] = [
        { label: 'Phonetic', value: 'phonetic' },
        { label: 'Phonetic HH', value: 'phonetic-hh' },
        { label: 'Typewriter', value: 'typewriter' },
    ];

    /**
     * Translate a single character based on current layout
     */
    function translate(char: string): string {
        if (!enabled.value) return char;

        const index = TRANS_FROM.indexOf(char);
        if (index === -1) return char;

        // Get character from layout map using Array.from for proper Unicode handling
        const layoutChars = Array.from(currentMap.value);
        return layoutChars[index] || char;
    }

    /**
     * Handle keydown event for input/textarea elements
     */
    function handleKeyDown(e: KeyboardEvent, el: HTMLInputElement | HTMLTextAreaElement): void {
        // Skip if disabled or modifier keys are pressed
        if (!enabled.value || e.ctrlKey || e.metaKey || e.altKey) return;

        const char = e.key;
        // Only translate single printable characters
        if (char.length !== 1) return;

        const translated = translate(char);
        // If no translation needed, let the browser handle it
        if (translated === char) return;

        e.preventDefault();

        const start = el.selectionStart || 0;
        const end = el.selectionEnd || 0;
        const value = el.value;

        // Insert translated character at cursor position
        el.value = value.slice(0, start) + translated + value.slice(end);
        el.selectionStart = el.selectionEnd = start + 1;

        // Trigger input event for v-model reactivity
        el.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function toggle(): void {
        enabled.value = !enabled.value;
    }

    function enable(): void {
        enabled.value = true;
    }

    function disable(): void {
        enabled.value = false;
    }

    function setLayout(newLayout: DhivehiLayout): void {
        layout.value = newLayout;
    }

    return {
        enabled,
        layout,
        toggle,
        enable,
        disable,
        setLayout,
        translate,
        handleKeyDown,
        direction,
        layoutOptions,
    };
}

/**
 * Create a shared/global Dhivehi keyboard instance
 * Useful when you want the same keyboard state across multiple components
 */
const globalEnabled = ref(false);
const globalLayout = ref<DhivehiLayout>('phonetic');

export function useGlobalDhivehiKeyboard(): UseDhivehiKeyboardReturn {
    const currentMap = computed(() => LAYOUTS[globalLayout.value]);
    const direction = computed<'ltr' | 'rtl'>(() => (globalEnabled.value ? 'rtl' : 'ltr'));

    const layoutOptions: { label: string; value: DhivehiLayout }[] = [
        { label: 'Phonetic', value: 'phonetic' },
        { label: 'Phonetic HH', value: 'phonetic-hh' },
        { label: 'Typewriter', value: 'typewriter' },
    ];

    function translate(char: string): string {
        if (!globalEnabled.value) return char;

        const index = TRANS_FROM.indexOf(char);
        if (index === -1) return char;

        const layoutChars = Array.from(currentMap.value);
        return layoutChars[index] || char;
    }

    function handleKeyDown(e: KeyboardEvent, el: HTMLInputElement | HTMLTextAreaElement): void {
        if (!globalEnabled.value || e.ctrlKey || e.metaKey || e.altKey) return;

        const char = e.key;
        if (char.length !== 1) return;

        const translated = translate(char);
        if (translated === char) return;

        e.preventDefault();

        const start = el.selectionStart || 0;
        const end = el.selectionEnd || 0;
        const value = el.value;

        el.value = value.slice(0, start) + translated + value.slice(end);
        el.selectionStart = el.selectionEnd = start + 1;

        el.dispatchEvent(new Event('input', { bubbles: true }));
    }

    return {
        enabled: globalEnabled,
        layout: globalLayout,
        toggle: () => {
            globalEnabled.value = !globalEnabled.value;
        },
        enable: () => {
            globalEnabled.value = true;
        },
        disable: () => {
            globalEnabled.value = false;
        },
        setLayout: (newLayout: DhivehiLayout) => {
            globalLayout.value = newLayout;
        },
        translate,
        handleKeyDown,
        direction,
        layoutOptions,
    };
}
