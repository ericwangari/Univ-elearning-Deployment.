<?php
$languageNames = [
    'en' => 'English',
    'tr' => 'Türkçe',
];
$languageRedirect = $_SERVER['REQUEST_URI'] ?? 'index.php?page=login';
?>
<form method="GET" action="index.php" class="language-selector-form">
    <input type="hidden" name="page" value="set-language">
    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($languageRedirect, ENT_QUOTES, 'UTF-8'); ?>">
    <label class="visually-hidden" for="languageSelect">Language</label>
    <select id="languageSelect" name="lang" class="form-select form-select-sm" aria-label="Language" onchange="this.form.submit()">
        <?php foreach (AVAILABLE_LANGUAGES as $languageCode): ?>
            <option value="<?php echo htmlspecialchars($languageCode, ENT_QUOTES, 'UTF-8'); ?>" <?php echo getCurrentLanguage() === $languageCode ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($languageNames[$languageCode], ENT_QUOTES, 'UTF-8'); ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>