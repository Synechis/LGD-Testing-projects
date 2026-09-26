(function scarfolkEqualHeightsBlocksScript(Drupal) {
  Drupal.behaviors.scarfolkEqualHeightsBlocks = {
    attach(context) {

      const layouts = once('allLayouts', '.layout', context);
      const blocksToEqualiseHeights = [
        '.ia-block',
        '.link-block',
        '.newsroom-teaser',
      ];

      function equaliseHeightsOfTheseBlocks(layoutWithBlocks, typeOfBlock) {
        layoutWithBlocks.forEach((item) => {
          const blocksInLayout = item.querySelectorAll(typeOfBlock);
          const blockHeights = [];

          function removeExistingHeights() {
            blocksInLayout.forEach((block) => {
              block.style.height = '';
            });
          }

          function handleGetHeights() {
            blocksInLayout.forEach((block) => {
              blockHeights.push(block.offsetHeight);
            });
            const tallestBlock = Math.max(...blockHeights);
            blocksInLayout.forEach((block) => {
              block.style.height = `${tallestBlock}px`;
            });
          }

          // We need a setTimeout here because the images take just
          // a tiny bit to load, which causes the layout to be set
          // before they are in place, then they get positioned
          // wrong on first load.
          setTimeout(() => {
            removeExistingHeights();
            handleGetHeights();
          }, 250);
        });
      }

      function handleEqualise() {
        blocksToEqualiseHeights.forEach((blockSelector) => {
          const layoutsWithBlocks = layouts.filter((item) =>
            item.querySelector(blockSelector),
          );
          if (layoutsWithBlocks.length) {
            equaliseHeightsOfTheseBlocks(layoutsWithBlocks, blockSelector);
          }
        });
      }

      handleEqualise();

      window.addEventListener(
        'resize',
        Drupal.debounce(handleEqualise, 250, true),
      );
    },
  };
})(Drupal);
