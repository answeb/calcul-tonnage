{*
* Calcul Tonnage Module
*
* @category  Module
* @author    Claude
* @copyright 2025
* @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}

<button type="button" class="btn btn-light btn-block text-truncate" data-toggle="modal" data-target="#tonnageCalculatorModal">
    {l s='Calcul de tonnage' d='Modules.Calcultonnage.Front'}
    <span id="tonnageCalculatorResult" class="font-sm"></span>
</button>

<!-- Modal -->
<div class="modal fade" id="tonnageCalculatorModal" tabindex="-1" role="dialog"
     aria-labelledby="tonnageCalculatorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"
                    id="tonnageCalculatorModalLabel">{l s='Calculateur de tonnage' d='Modules.Calcultonnage.Front'}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-3">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="surface">{l s='Surface (m²)' d='Modules.Calcultonnage.Front'}</label>
                            <input type="number" class="form-control" id="surface" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="thickness">{l s='Épaisseur (cm)' d='Modules.Calcultonnage.Front'}</label>
                            <input type="number" class="form-control" id="thickness" step="0.1" min="0" required>
                        </div>
                        <button id="tonnageCalculator"
                                class="btn btn-primary">{l s='Calculer' d='Modules.Calcultonnage.Front'}</button>
                    </div>
                    <div class="col-md-6">
                        <div class="text-center bg-light p-4 mb-3">
                            <h2>{l s='Tonnage nécessaire' d='Modules.Calcultonnage.Front'}</h2>
                            <div class="">
                                <span id="tonnageValue" class="display-1">-</span>
                                <span class="tonnage-unit display-4">kg</span>
                            </div>
                        </div>
                        <p>{l s='Prévoyez une marge de 10% sur le tonnage calculé' d='Modules.Calcultonnage.Front'}</p>

                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                        data-dismiss="modal">{l s='Fermer' d='Modules.Calcultonnage.Front'}</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Density value from PHP
        const density = {$density|floatval};
        const resultElement = document.getElementById('tonnageValue');
        let result;
        document.getElementById('tonnageCalculator').addEventListener('click', function (e) {
            e.preventDefault();
            let surface = parseFloat(document.getElementById('surface').value);
            let thickness = parseFloat(document.getElementById('thickness').value);
            // Convert thickness from cm to m
            thickness = thickness / 100;
            // Calculate tonnage: density * surface * thickness
            const tonnage = density * surface * thickness;
            // Display result with 2 decimal places
            resultElement.textContent = tonnage.toFixed(2);
            result = '(' + tonnage.toFixed(2) + ' kg)';
            document.getElementById('tonnageCalculatorResult').textContent = result;
            // Stocker en localstorage
            if (typeof sessionStorage !== 'undefined') {
                sessionStorage.setItem('tonnageCalculator-' + '{$product.id}', result)
            }
        });
        if (typeof sessionStorage !== 'undefined') {
            result = sessionStorage.getItem('tonnageCalculator-' + '{$product.id}');
            if (result) {
                document.getElementById('tonnageCalculatorResult').textContent = result;
            }
        }
    });
</script>
