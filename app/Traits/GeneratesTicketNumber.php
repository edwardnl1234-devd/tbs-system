<?php

namespace App\Traits;

trait GeneratesTicketNumber
{
    /**
     * Generate ticket number with format: NNNN/XX/P/YY
     * - NNNN: Sequential number for today (4 digits)
     * - XX: First letters of first and second word of company name
     * - P: Product type code (I = Inti, C = Cangkang, T = TBS)
     * - YY: Last 2 digits of year
     * 
     * Example: 0001/AG/I/26 (for PT Agung Gas, Inti product, year 2026)
     * 
     * @param int $sequence Sequential number for today
     * @param string $companyName Company/Supplier name
     * @param string $productType Product type (TBS, Inti, Cangkang)
     * @return string
     */
    public function generateTicketNumber(int $sequence, string $companyName, string $productType = 'TBS'): string
    {
        // Format sequence to 4 digits
        $sequenceFormatted = str_pad($sequence, 4, '0', STR_PAD_LEFT);
        
        // Extract company code from name
        $companyCode = $this->extractCompanyCode($companyName);
        
        // Get product type code
        $productCode = $this->getProductTypeCode($productType);
        
        // Get last 2 digits of year
        $year = now()->format('y');
        
        return "{$sequenceFormatted}/{$companyCode}/{$productCode}/{$year}";
    }

    /**
     * Generate queue number with format: NNNN/XX/YY
     * - NNNN: Sequential number for today (4 digits)
     * - XX: First letters of first and second word of company name
     * - YY: Last 2 digits of year
     * 
     * Example: 0001/AG/26 (for PT Agung Gas, year 2026)
     * 
     * @param int $sequence Sequential number for today
     * @param string $companyName Company/Supplier name
     * @return string
     */
    public function generateQueueNumber(int $sequence, string $companyName): string
    {
        // Format sequence to 4 digits
        $sequenceFormatted = str_pad($sequence, 4, '0', STR_PAD_LEFT);
        
        // Extract company code from name
        $companyCode = $this->extractCompanyCode($companyName);
        
        // Get last 2 digits of year
        $year = now()->format('y');
        
        return "{$sequenceFormatted}/{$companyCode}/{$year}";
    }

    /**
     * Extract company code from company name
     * Takes first letter of first word and first letter of second word
     * Ignores common prefixes like PT, CV, UD
     * 
     * Examples:
     * - "PT Agung Gas" -> "AG"
     * - "CV Bina Mandiri" -> "BM"
     * - "Jaya Makmur" -> "JM"
     * - "Sumber Rejeki Abadi" -> "SR"
     * 
     * @param string $companyName
     * @return string
     */
    protected function extractCompanyCode(string $companyName): string
    {
        // Clean and uppercase the name
        $name = strtoupper(trim($companyName));
        
        // Remove common prefixes
        $prefixes = ['PT', 'PT.', 'CV', 'CV.', 'UD', 'UD.', 'PD', 'PD.', 'TOKO', 'TB', 'TB.'];
        foreach ($prefixes as $prefix) {
            if (str_starts_with($name, $prefix . ' ')) {
                $name = trim(substr($name, strlen($prefix)));
                break;
            }
        }
        
        // Split into words
        $words = preg_split('/\s+/', $name);
        
        // Get first letter of first word
        $firstLetter = isset($words[0]) && strlen($words[0]) > 0 ? $words[0][0] : 'X';
        
        // Get first letter of second word, or second letter of first word if no second word
        if (isset($words[1]) && strlen($words[1]) > 0) {
            $secondLetter = $words[1][0];
        } elseif (isset($words[0]) && strlen($words[0]) > 1) {
            $secondLetter = $words[0][1];
        } else {
            $secondLetter = 'X';
        }
        
        return $firstLetter . $secondLetter;
    }

    /**
     * Get product type code
     * - I = Inti (Kernel)
     * - C = Cangkang (Shell)
     * - T = TBS
     * 
     * @param string $productType
     * @return string
     */
    protected function getProductTypeCode(string $productType): string
    {
        return match (strtolower($productType)) {
            'inti', 'kernel' => 'I',
            'cangkang', 'shell' => 'C',
            default => 'T',
        };
    }
}
