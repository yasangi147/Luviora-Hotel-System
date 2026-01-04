# PowerShell script to add name attributes to all form fields in booking.php

$filePath = "booking.php"
$content = Get-Content $filePath -Raw

# Define replacements: id -> id + name
$replacements = @{
    'id="viewPreference"' = 'id="viewPreference" name="viewPreference"'
    'id="bedPreference"' = 'id="bedPreference" name="bedPreference"'
    'id="floorPreference"' = 'id="floorPreference" name="floorPreference"'
    'id="accessibleRoom"' = 'id="accessibleRoom" name="accessibleRoom"'
    'id="connectingRooms"' = 'id="connectingRooms" name="connectingRooms"'
    'id="quietRoom"' = 'id="quietRoom" name="quietRoom"'
    'id="nearElevator"' = 'id="nearElevator" name="nearElevator"'
    'id="dietVegetarian"' = 'id="dietVegetarian" name="dietVegetarian"'
    'id="dietVegan"' = 'id="dietVegan" name="dietVegan"'
    'id="dietGlutenFree"' = 'id="dietGlutenFree" name="dietGlutenFree"'
    'id="dietHalal"' = 'id="dietHalal" name="dietHalal"'
    'id="dietKosher"' = 'id="dietKosher" name="dietKosher"'
    'id="dietOther"' = 'id="dietOther" name="dietOther"'
    'id="additionalRequests"' = 'id="additionalRequests" name="additionalRequests"'
    'id="paymentMethod"' = 'id="paymentMethod" name="paymentMethod"'
    'id="cardNumber"' = 'id="cardNumber" name="cardNumber"'
    'id="cardholderName"' = 'id="cardholderName" name="cardholderName"'
    'id="expiryMonth"' = 'id="expiryMonth" name="expiryMonth"'
    'id="expiryYear"' = 'id="expiryYear" name="expiryYear"'
    'id="cvv"' = 'id="cvv" name="cvv"'
    'id="bookingForOther"' = 'id="bookingForOther" name="bookingForOther"'
    'id="guestName"' = 'id="guestName" name="guestName"'
    'id="guestEmail"' = 'id="guestEmail" name="guestEmail"'
    'id="termsAgree"' = 'id="termsAgree" name="termsAgree"'
}

# Apply replacements
foreach ($key in $replacements.Keys) {
    $content = $content -replace [regex]::Escape($key), $replacements[$key]
}

# Save the file
$content | Set-Content $filePath -NoNewline

Write-Host "✅ Successfully added name attributes to all form fields!" -ForegroundColor Green
Write-Host "Updated file: $filePath" -ForegroundColor Cyan

