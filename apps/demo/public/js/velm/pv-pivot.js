(() => {
  // resources/js/src/pv-pivot.entry.js
  document.addEventListener("alpine:init", () => {
    Alpine.data("pvPivotToolbar", (cfg) => ({
      model: cfg.model,
      module: cfg.module || "",
      view: cfg.view || "",
      rowGroupby: cfg.initRowGroupby ? cfg.initRowGroupby.split(",").filter(Boolean) : [],
      colGroupby: cfg.initColGroupby ? cfg.initColGroupby.split(",").filter(Boolean) : [],
      measures: cfg.initMeasures ? cfg.initMeasures.split(",").filter(Boolean) : ["__count"],
      groupable: cfg.groupable || [],
      measurable: cfg.measurable || [],
      searchText: cfg.search || "",
      loading: false,
      hasFetched: false,
      tableHtml: "",
      get subtitle() {
        return "";
      },
      labelFor(spec) {
        const field = this.groupable.find((entry) => entry.value === spec);
        return field ? field.label : spec;
      },
      measureLabelFor(spec) {
        const field = this.measurable.find((entry) => entry.value === spec);
        return field ? field.label : spec;
      },
      init() {
        this.$nextTick(() => this.$refs.searchInput?.focus({ preventScroll: true }));
      },
      addRowGroupby(value) {
        if (value && !this.rowGroupby.includes(value)) {
          this.rowGroupby.push(value);
          this.fetchData();
        }
      },
      removeRowGroupby(index) {
        this.rowGroupby.splice(index, 1);
        this.fetchData();
      },
      addColGroupby(value) {
        if (value && !this.colGroupby.includes(value)) {
          this.colGroupby.push(value);
          this.fetchData();
        }
      },
      removeColGroupby(index) {
        this.colGroupby.splice(index, 1);
        this.fetchData();
      },
      addMeasure(value) {
        if (value && !this.measures.includes(value)) {
          this.measures.push(value);
          this.fetchData();
        }
      },
      removeMeasure(index) {
        if (this.measures.length > 1) {
          this.measures.splice(index, 1);
          this.fetchData();
        }
      },
      swapAxes() {
        [this.rowGroupby, this.colGroupby] = [[...this.colGroupby], [...this.rowGroupby]];
        this.fetchData();
      },
      async fetchData() {
        this.loading = true;
        try {
          const params = new URLSearchParams({
            model: this.model,
            row_groupby: this.rowGroupby.join(","),
            col_groupby: this.colGroupby.join(","),
            measures: this.measures.join(","),
            search: this.searchText
          });
          if (this.module) {
            params.set("module", this.module);
          }
          if (this.view) {
            params.set("view", this.view);
          }
          const response = await fetch(`/api/pivot/data?${params}`, { credentials: "same-origin" });
          if (!response.ok) {
            throw new Error("pivot fetch failed");
          }
          const data = await response.json();
          this.tableHtml = this._buildTable(data);
          this.hasFetched = true;
        } catch {
          window.pvAlert?.("Could not load pivot data.", { variant: "warning" });
        } finally {
          this.loading = false;
        }
      },
      _buildTable(data) {
        const measureCount = data.measure_count;
        const rowSpecs = data.row_specs;
        const rowLabelCount = rowSpecs.length || 1;
        let html = '<table class="min-w-full text-sm">';
        html += '<thead class="bg-surface-muted/50">';
        const headerLevelCount = (data.header_levels || []).length;
        const rowspan = headerLevelCount + 1;
        if (data.header_levels && data.header_levels.length) {
          data.header_levels.forEach((level, levelIndex) => {
            html += "<tr>";
            if (levelIndex === 0) {
              html += `<th colspan="${rowLabelCount}" rowspan="${rowspan}"
                            class="sticky left-0 z-10 bg-surface-muted/50 border-b border-r border-default
                                   px-3 py-2 text-left text-2xs font-semibold uppercase tracking-wider text-body-subtle">
                            ${rowSpecs.map((spec) => this.labelFor(spec)).join(" / ")}
                        </th>`;
            }
            level.forEach((header) => {
              html += `<th colspan="${header.colspan}"
                            class="border-b border-default px-3 py-2 text-center text-2xs
                                   font-semibold uppercase tracking-wider text-body-subtle">
                            ${header.label}
                        </th>`;
            });
            if (levelIndex === 0) {
              html += `<th colspan="${data.grand_header.colspan}" rowspan="${rowspan}"
                            class="border-b border-l border-default px-3 py-2 text-center text-2xs
                                   font-semibold uppercase tracking-wider text-fg-brand bg-brand-soft">
                            ${data.grand_header.label}
                        </th>`;
            }
            html += "</tr>";
          });
        } else {
          html += "<tr>";
          html += `<th colspan="${rowLabelCount}" rowspan="${rowspan}"
                    class="sticky left-0 z-10 bg-surface-muted/50 border-b border-r border-default
                           px-3 py-2 text-left text-2xs font-semibold uppercase tracking-wider text-body-subtle">
                    ${rowSpecs.map((spec) => this.labelFor(spec)).join(" / ")}
                </th>`;
          html += `<th colspan="${measureCount}"
                    class="border-b border-l border-default px-3 py-2 text-center text-2xs
                           font-semibold uppercase tracking-wider text-fg-brand bg-brand-soft">
                    ${data.grand_header.label}
                </th>`;
          html += "</tr>";
        }
        html += "<tr>";
        (data.measure_label_row || []).forEach((measure) => {
          html += `<th class="border-b border-default px-3 py-1.5 text-right
                                    text-2xs font-medium text-body-subtle">${measure.label}</th>`;
        });
        html += "</tr>";
        html += "</thead>";
        html += '<tbody class="divide-y divide-default">';
        if (data.body_rows.length === 0) {
          const span = rowLabelCount + measureCount + data.col_combos_count * measureCount;
          html += `<tr><td colspan="${span}"
                    class="px-3 py-8 text-center text-sm text-body-subtle">
                    No data to pivot. Adjust the controls above.
                </td></tr>`;
        } else {
          data.body_rows.forEach((row) => {
            html += '<tr class="hover:bg-surface-muted/40 transition-colors">';
            row.labels.forEach((label, labelIndex) => {
              const stickyClass = labelIndex === 0 ? " sticky left-0 bg-surface z-[1]" : "";
              html += `<td class="px-3 py-2 text-sm font-medium text-heading${stickyClass}">${label}</td>`;
            });
            row.cells.forEach((cell) => {
              const totalClass = cell.is_total ? " font-semibold text-fg-brand bg-brand-soft/40" : "";
              html += `<td class="px-3 py-2 text-right tabular-nums${totalClass}">${cell.display}</td>`;
            });
            html += "</tr>";
          });
        }
        html += "</tbody>";
        if (data.body_rows.length > 0) {
          html += "<tfoot>";
          html += `<tr class="bg-surface-muted/50 font-semibold text-fg-brand">
                    <td colspan="${rowLabelCount}"
                        class="sticky left-0 z-[1] bg-surface-muted/50
                               px-3 py-2 text-2xs uppercase tracking-wider">Total</td>`;
          (data.col_totals || []).forEach((cell) => {
            html += `<td class="px-3 py-2 text-right tabular-nums">${cell.display}</td>`;
          });
          html += "</tr></tfoot>";
        }
        html += "</table>";
        return html;
      }
    }));
  });
})();
